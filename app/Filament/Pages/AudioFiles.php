<?php
// ===== QURBA admin: upload app sounds (background, adhan, 99 Names) into public/audio =====
namespace App\Filament\Pages;

use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use App\Support\NameVoices;
use App\Support\Quran\NamesOfAllah;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AudioFiles extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    /** One-off uploads from the 99 Names grid, keyed by name number */
    public array $nameUploads = [];

    /** Which voice the 99 Names grid is showing */
    public string $voice = 'standard';

    /** MIME types browsers report for .mp3 files */
    private const MP3 = ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'audio/mpeg3', 'audio/x-mp3'];

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $title = 'Audio library';
    protected static ?string $navigationLabel = 'Audio library';
    protected static string $view = 'filament.pages.audio-files';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return R::is(R::CONTENT);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    private static function single(string $field, string $label, string $file, string $help): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($field)->label($label)->helperText($help)
            ->disk('audio')->visibility('public')
            ->acceptedFileTypes(self::MP3)->maxSize(20 * 1024)
            ->getUploadedFileNameForStorageUsing(fn () => $file);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Section::make('Upload sounds')->description('MP3 only. Upload only recordings you have permission to publish. A new upload replaces the old file.')->collapsible()->schema([
                self::single('ambient', 'Background sound', 'ambient.mp3', 'Soft sound that loops quietly in the app.'),
                self::single('adhan', 'Adhan', 'adhan.mp3', 'Played at prayer time.'),
                self::single('adhan_fajr', 'Fajr adhan (optional)', 'adhan-fajr.mp3', 'Used for Fajr instead of the main adhan.'),
            ]),
            Forms\Components\Section::make(fn () => 'Upload many names at once — ' . NameVoices::get($this->voice)['label'] . ' voice')->description('Name each file by its number in the list: 1.mp3 … 99.mp3.')->collapsible()->collapsed()->schema([
                Forms\Components\FileUpload::make('names')->label('Name recordings')->multiple()->maxFiles(99)
                    ->disk('audio')->directory(fn () => NameVoices::get($this->voice)['dir'])->visibility('public')->preserveFilenames()
                    ->acceptedFileTypes(self::MP3)->maxSize(20 * 1024)
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file) {
                        $n = (int) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        return ($n >= 1 && $n <= 99) ? "{$n}.mp3" : 'invalid-' . $file->getClientOriginalName();
                    }),
            ]),
        ]);
    }

    public function save(): void
    {
        $this->form->getState(); // stores the uploads
        $bad = collect(Storage::disk('audio')->files(NameVoices::get($this->voice)['dir']))->filter(fn ($f) => str_starts_with(basename($f), 'invalid-'));
        $bad->each(fn ($f) => Storage::disk('audio')->delete($f));
        $this->form->fill();
        $n = $bad->count();
        Notification::make()->title('Audio saved')
            ->body($n ? "{$n} name file(s) were skipped: name them 1.mp3 … 99.mp3." : null)
            ->{$n ? 'warning' : 'success'}()->send();
    }

    /** Replace one name's recording straight from the grid */
    public function updatedNameUploads($file, $key): void
    {
        $n = (int) $key;
        $this->validate(["nameUploads.{$n}" => 'file|mimetypes:audio/mpeg,audio/mp3,audio/x-mpeg,audio/mpeg3,audio/x-mp3|max:20480']);
        abort_unless($n >= 1 && $n <= 99, 422);
        $file->storeAs(NameVoices::get($this->voice)['dir'], "{$n}.mp3", 'audio');
        unset($this->nameUploads[$n]);
        Notification::make()->title("Name {$n} saved")->success()->send();
    }

    public function deleteName(int $n): void
    {
        abort_unless(R::is(R::CONTENT) && $n >= 1 && $n <= 99, 403);
        Storage::disk('audio')->delete(NameVoices::get($this->voice)['dir'] . "/{$n}.mp3");
        Notification::make()->title("Name {$n} removed")->send();
    }

    /** A full recitation uploaded into one name's slot: move it to the complete-recitation slot */
    public function useAsFull(int $n): void
    {
        abort_unless(R::is(R::CONTENT) && $n >= 1 && $n <= 99, 403);
        $d = Storage::disk('audio');
        $v = NameVoices::get($this->voice);
        if (! $d->exists("{$v['dir']}/{$n}.mp3")) return;
        $d->delete($v['full']);
        $d->move("{$v['dir']}/{$n}.mp3", $v['full']);
        Notification::make()->title('Saved as the complete 99 Names recitation')->success()->send();
    }

    /** Upload / replace / remove the complete recitation of the selected voice */
    public $fullUpload = null;

    public function updatedFullUpload(): void
    {
        $this->validate(['fullUpload' => 'file|mimetypes:audio/mpeg,audio/mp3,audio/x-mpeg,audio/mpeg3,audio/x-mp3|max:20480']);
        $this->fullUpload->storeAs('', NameVoices::get($this->voice)['full'], 'audio');
        $this->fullUpload = null;
        Notification::make()->title('Complete recitation saved')->success()->send();
    }

    public function deleteFull(): void
    {
        abort_unless(R::is(R::CONTENT), 403);
        Storage::disk('audio')->delete(NameVoices::get($this->voice)['full']);
        Notification::make()->title('Removed')->send();
    }

    public function fullUrl(): ?string
    {
        $f = NameVoices::get($this->voice)['full'];
        $d = Storage::disk('audio');
        return $d->exists($f) ? asset("audio/{$f}") . '?v=' . $d->lastModified($f) : null;
    }

    public function setVoice(string $key): void
    {
        $this->voice = array_key_exists($key, NameVoices::ALL) ? $key : 'standard';
    }

    public function deleteSound(string $file): void
    {
        abort_unless(R::is(R::CONTENT) && in_array($file, ['ambient.mp3', 'adhan.mp3', 'adhan-fajr.mp3'], true), 403);
        Storage::disk('audio')->delete($file);
        Notification::make()->title('Removed')->send();
    }

    /** Sound slots with their public URL (cache-busted) when present */
    public function sounds(): array
    {
        $d = Storage::disk('audio');
        return collect(['ambient.mp3' => 'Background sound', 'adhan.mp3' => 'Adhan', 'adhan-fajr.mp3' => 'Fajr adhan'])
            ->map(fn ($label, $file) => ['label' => $label, 'file' => $file,
                'url' => $d->exists($file) ? asset("audio/{$file}") . '?v=' . $d->lastModified($file) : null])->values()->all();
    }

    /** The 99 names with their recording URL, if uploaded */
    public function names(): array
    {
        $d = Storage::disk('audio');
        $dir = NameVoices::get($this->voice)['dir'];
        return array_map(fn ($x) => $x + ['url' => $d->exists("{$dir}/{$x['n']}.mp3")
            ? asset("audio/{$dir}/{$x['n']}.mp3") . '?v=' . $d->lastModified("{$dir}/{$x['n']}.mp3") : null], NamesOfAllah::all());
    }

    /** Server upload limit in MB (the smaller of PHP's upload_max_filesize and post_max_size) */
    public function serverLimitMb(): float
    {
        $toBytes = function (string $v): int {
            $v = trim($v);
            $n = (int) $v;
            return match (strtolower(substr($v, -1))) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
        };
        $limits = array_filter([$toBytes((string) ini_get('upload_max_filesize')), $toBytes((string) ini_get('post_max_size'))]);
        return $limits ? round(min($limits) / 1024 ** 2, 1) : 0;
    }

    /** What is installed, for the status table */
    public function status(): array
    {
        $d = Storage::disk('audio');

        return [
            'Background sound' => $d->exists('ambient.mp3'),
            'Adhan' => $d->exists('adhan.mp3'),
            'Fajr adhan' => $d->exists('adhan-fajr.mp3'),
            ...collect(NameVoices::ALL)->mapWithKeys(fn ($v, $k) => ["99 Names — {$v['label']}" => count(NameVoices::recorded($k)) . ' / 99' . ($d->exists($v['full']) ? ' + complete' : '')])->all(),
        ];
    }
}
