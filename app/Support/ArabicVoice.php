<?php
// ===== QURBA: Arabic neural text-to-speech (Microsoft Azure Speech) for adhkar and kalimas =====
// Never used for the Quran. Generated audio is saved once and must be approved by a reviewer.
namespace App\Support;

use Illuminate\Support\Facades\Http;

class ArabicVoice
{
    public static function configured(): bool
    {
        return (bool) (config('services.azure_tts.key') && config('services.azure_tts.region'));
    }

    /** MP3 bytes of the Arabic text read slowly and clearly. */
    public static function synthesize(string $arabic): string
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
