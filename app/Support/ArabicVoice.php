<?php
// ===== QURBA: Arabic AI voice (text-to-speech) for adhkar and kalimas =====
// Providers: Google Gemini TTS (default) or Microsoft Azure Speech. Never used for the Quran.
// Generated audio is saved once and must be approved by a reviewer before users hear it.
namespace App\Support;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ArabicVoice
{
    public static function provider(): string
    {
        return config('services.arabic_voice.provider', 'gemini');
    }

    public static function configured(): bool
    {
        return match (self::provider()) {
            'azure' => (bool) (config('services.azure_tts.key') && config('services.azure_tts.region')),
            default => (bool) config('services.gemini.key'),
        };
    }

    /** Voice name used, for the audit log. */
    public static function voiceName(): string
    {
        return self::provider() === 'azure' ? (string) config('services.azure_tts.voice') : 'gemini:'.config('services.gemini.voice');
    }

    /** @return array{0: string, 1: string} [audio bytes, file extension] */
    public static function synthesize(string $arabic): array
    {
        return self::provider() === 'azure' ? [self::azure($arabic), 'mp3'] : self::gemini($arabic);
    }

    private static function gemini(string $arabic): array
    {
        // Short style instruction first; if Gemini answers with text instead of audio, retry with the Arabic alone.
        $res = self::geminiRequest('Say slowly and clearly: '.$arabic);
        if (! $res->json('candidates.0.content.parts.0.inlineData.data')) $res = self::geminiRequest($arabic);

        $data = $res->json('candidates.0.content.parts.0.inlineData.data');
        if (! $res->successful() || ! $data) {
            throw new \RuntimeException('Gemini voice failed (HTTP '.$res->status().'): '.($res->json('error.message') ?? 'no audio returned').' Check GEMINI_API_KEY.');
        }
        // Gemini returns raw 16-bit mono PCM; rate is in the mime type (e.g. audio/L16;codec=pcm;rate=24000)
        preg_match('/rate=(\d+)/', (string) $res->json('candidates.0.content.parts.0.inlineData.mimeType'), $m);
        return self::pcmToFile(base64_decode($data), (int) ($m[1] ?? 24000));
    }

    private static function geminiRequest(string $text): \Illuminate\Http\Client\Response
    {
        $model = config('services.gemini.tts_model', 'gemini-2.5-flash-preview-tts');
        return Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])->timeout(60)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [['parts' => [['text' => $text]]]],
                'generationConfig' => [
                    'responseModalities' => ['AUDIO'],
                    'speechConfig' => ['voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => config('services.gemini.voice', 'Charon')]]],
                ],
            ]);
    }

    /** MP3 when ffmpeg is installed on the server, otherwise WAV (plays everywhere, just larger). */
    private static function pcmToFile(string $pcm, int $rate): array
    {
        $ffmpeg = (new ExecutableFinder())->find('ffmpeg');
        if ($ffmpeg) {
            $p = new Process([$ffmpeg, '-hide_banner', '-loglevel', 'error', '-f', 's16le', '-ar', (string) $rate, '-ac', '1', '-i', 'pipe:0', '-b:a', '64k', '-f', 'mp3', 'pipe:1']);
            $p->setInput($pcm)->setTimeout(60)->run();
            if ($p->isSuccessful() && $p->getOutput() !== '') return [$p->getOutput(), 'mp3'];
        }
        $header = 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16).'data'.pack('V', strlen($pcm));
        return [$header.$pcm, 'wav'];
    }

    private static function azure(string $arabic): string
    {
        $voice = config('services.azure_tts.voice', 'ar-SA-HamedNeural');
        $text = htmlspecialchars($arabic, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $ssml = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="ar-SA">'
            ."<voice name=\"{$voice}\"><prosody rate=\"-15%\">{$text}</prosody></voice></speak>";

        $res = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.azure_tts.key'),
            'X-Microsoft-OutputFormat' => 'audio-24khz-96kbitrate-mono-mp3',
            'User-Agent' => 'Qurba',
        ])->withBody($ssml, 'application/ssml+xml')->timeout(30)
            ->post('https://'.config('services.azure_tts.region').'.tts.speech.microsoft.com/cognitiveservices/v1');

        if (! $res->successful() || $res->body() === '') {
            throw new \RuntimeException('Text-to-speech failed (HTTP '.$res->status().'). Check AZURE_TTS_KEY and AZURE_TTS_REGION.');
        }
        return $res->body();
    }
}
