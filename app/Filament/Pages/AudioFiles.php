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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AudioFiles extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $title = 'Audio files';
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
            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])->maxSize(15 * 1024)
            ->getUploadedFileNameForStorageUsing(fn () => $file);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Section::make('Sounds')->description('MP3 only. Upload only recordings you have permission to publish. A new upload replaces the old file.')->schema([
                self::single('ambient', 'Background sound', 'ambient.mp3', 'Soft sound that loops quietly in the app.'),
                self::single('adhan', 'Adhan', 'adhan.mp3', 'Played at prayer time.'),
                self::single('adhan_fajr', 'Fajr adhan (optional)', 'adhan-fajr.mp3', 'Used for Fajr instead of the main adhan.'),
            ]),
            Forms\Components\Section::make('99 Names of Allah')->description('Name each file by its number in the list: 1.mp3 … 99.mp3.')->schema([
                Forms\Components\FileUpload::make('names')->label('Name recordings')->multiple()->maxFiles(99)
                    ->disk('audio')->directory('names')->visibility('public')->preserveFilenames()
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])->maxSize(5 * 1024)
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
        $bad = collect(Storage::disk('audio')->files('names'))->filter(fn ($f) => str_starts_with(basename($f), 'invalid-'));
        $bad->each(fn ($f) => Storage::disk('audio')->delete($f));
        $this->form->fill();
        $n = $bad->count();
        Notification::make()->title('Audio saved')
            ->body($n ? "{$n} name file(s) were skipped: name them 1.mp3 … 99.mp3." : null)
            ->{$n ? 'warning' : 'success'}()->send();
    }

    /** What is installed, for the status table */
    public function status(): array
    {
        $d = Storage::disk('audio');
        $names = collect($d->files('names'))->map(fn ($f) => (int) basename($f, '.mp3'))->filter(fn ($n) => $n >= 1 && $n <= 99)->unique();
        return [
            'Background sound' => $d->exists('ambient.mp3'),
            'Adhan' => $d->exists('adhan.mp3'),
            'Fajr adhan' => $d->exists('adhan-fajr.mp3'),
            '99 Names' => $names->count() . ' / 99',
        ];
    }
}
