<?php
// ===== QURBA admin: adhkar & duas with draft → in review → approved workflow =====
namespace App\Filament\Resources;

use App\Filament\Resources\AdhkarResource\Pages;
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Models\ContentAuditLog;
use App\Support\AdminRoles as R;
use App\Support\ArabicVoice;
use Illuminate\Support\Facades\Storage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdhkarResource extends Resource
{
    protected static ?string $model = Adhkar::class;
    protected static ?string $navigationIcon = 'heroicon-o-sun';
    protected static ?string $navigationGroup = 'Religious content';
    protected static ?string $navigationLabel = 'Adhkar & duas';
    protected static ?string $modelLabel = 'dhikr';
    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool { return R::is(R::CONTENT, R::REVIEWER); }
    public static function canCreate(): bool { return R::is(R::CONTENT); }
    public static function canEdit(Model $record): bool { return R::is(R::CONTENT); }
    public static function canDelete(Model $record): bool { return R::is() && $record->status !== 'approved'; }

    public static function getNavigationBadge(): ?string
    {
        $n = Adhkar::where('status', 'in_review')->count();
        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('adhkar_category_id')->label('Category')->required()
                ->options(fn () => AdhkarCategory::orderBy('sort')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()])),
            Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'in_review' => 'Send for review'])->default('draft')->required()
                ->helperText('Only a Religious Content Reviewer can approve. Editing an approved item sends it back to review.'),
            Forms\Components\Textarea::make('text_arabic')->label('Arabic text')->required()->rows(4)->columnSpanFull()
                ->extraInputAttributes(['dir' => 'rtl', 'style' => "font-family:'Amiri Quran','Amiri',serif;font-size:1.3rem;line-height:2"]),
            Forms\Components\Textarea::make('transliteration')->rows(2)->columnSpanFull(),
            Forms\Components\TextInput::make('reference')->label('Source / reference')->required()->placeholder('e.g. Sahih Muslim 2723'),
            Forms\Components\TextInput::make('repeat_count')->label('Target repetitions')->numeric()->minValue(1)->default(1)->required(),
            Forms\Components\Select::make('content_source_id')->label('Content source')
                ->relationship('source', 'name', fn ($query) => $query->where('type', 'adhkar'))->searchable()->preload(),
            Forms\Components\TextInput::make('sort')->numeric()->default(0),
            Forms\Components\FileUpload::make('audio_url')->label('Recitation audio (optional)')->columnSpanFull()
                ->disk('audio')->directory('duas')->visibility('public')
                ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])->maxSize(10 * 1024)
                ->helperText('MP3 of this dua being recited. Only upload recordings you have permission to publish.'),
            Forms\Components\TextInput::make('audio_start')->label('Starts at (seconds) in the category recording')->numeric()->minValue(0)->step(0.1)
                ->placeholder('e.g. 0, 34.5, 71')
                ->helperText('For read-along: the moment this dua begins in the category recording (Adhkar categories & audio). Leave empty if not used.'),
            Forms\Components\Repeater::make('translations')->relationship()->columnSpanFull()->maxItems(5)->defaultItems(0)
                ->schema([
                    Forms\Components\Select::make('language_code')->options(['en' => 'English', 'ur' => 'Urdu', 'ar' => 'Arabic (explanation)'])->required(),
                    Forms\Components\Textarea::make('text')->required()->rows(3),
                ])->columns(1)->itemLabel(fn (array $state) => strtoupper($state['language_code'] ?? '')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.slug')->label('Category')->badge(),
                Tables\Columns\TextColumn::make('text_arabic')->label('Arabic')->limit(80)->wrap()
                    ->extraAttributes(['dir' => 'rtl', 'style' => "font-family:'Amiri Quran','Amiri',serif;font-size:1.15rem"])->searchable(),
                Tables\Columns\TextColumn::make('reference')->searchable(),
                Tables\Columns\TextColumn::make('repeat_count')->label('×'),
                Tables\Columns\TextColumn::make('voice')->label('Voice')->badge()
                    ->getStateUsing(fn (Adhkar $r) => ! $r->audio_url ? null : (! $r->audio_ai ? 'Recording' : ($r->audio_approved ? 'AI · approved' : 'AI · needs review')))
                    ->color(fn (?string $state) => match ($state) { 'Recording', 'AI · approved' => 'success', 'AI · needs review' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'approved' => 'success', 'in_review' => 'warning', default => 'gray' }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['draft' => 'Draft', 'in_review' => 'In review', 'approved' => 'Approved']),
                Tables\Filters\SelectFilter::make('adhkar_category_id')->label('Category')
                    ->options(fn () => AdhkarCategory::orderBy('sort')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('aiVoice')->label('Generate AI voice')->icon('heroicon-o-speaker-wave')->color('gray')
                    ->visible(fn (Adhkar $r) => R::is(R::CONTENT) && ArabicVoice::configured() && (! $r->audio_url || $r->audio_ai))
                    ->requiresConfirmation()
                    ->modalDescription('An AI voice will read the Arabic text. A reviewer must listen and approve it before users hear it. A real recording uploaded later replaces it.')
                    ->action(function (Adhkar $record) {
                        try {
                            $mp3 = ArabicVoice::synthesize($record->text_arabic);
                        } catch (\Throwable $e) {
                            Notification::make()->title('Could not generate the voice')->body($e->getMessage())->danger()->send();
                            return;
                        }
                        $path = 'duas/ai-'.$record->id.'-'.now()->format('YmdHis').'.mp3';
                        Storage::disk('audio')->put($path, $mp3);
                        if ($record->audio_ai && $record->audio_url) Storage::disk('audio')->delete($record->audio_url);
                        Adhkar::$aiVoice = true;
                        try { $record->update(['audio_url' => $path, 'audio_ai' => true, 'audio_approved' => false]); } finally { Adhkar::$aiVoice = false; }
                        ContentAuditLog::record($record, 'ai_voice_generated', null, ['file' => $path, 'voice' => config('services.azure_tts.voice')]);
                        Notification::make()->title('AI voice generated — waiting for a reviewer to approve it')->success()->send();
                    }),
                Tables\Actions\Action::make('approveVoice')->label('Approve voice')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Adhkar $r) => R::is(R::REVIEWER) && $r->audio_ai && ! $r->audio_approved)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Adhkar $r) => new \Illuminate\Support\HtmlString('Listen to the whole recording and confirm every word and haraka is pronounced correctly.<br><audio controls preload="none" style="width:100%;margin-top:.75rem" src="'.e(asset('audio/'.$r->audio_url)).'"></audio>'))
                    ->action(function (Adhkar $record) {
                        $record->update(['audio_approved' => true]);
                        ContentAuditLog::record($record, 'ai_voice_approved');
                        Notification::make()->title('Voice approved')->success()->send();
                    }),
                Tables\Actions\Action::make('approve')->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (Adhkar $r) => R::is(R::REVIEWER) && $r->status !== 'approved')
                    ->requiresConfirmation()
                    ->modalDescription('Confirm the Arabic text, translation(s), repetitions and reference are accurate and authentic.')
                    ->form([Forms\Components\Textarea::make('note')->label('Review note')->required()])
                    ->action(function (Adhkar $record, array $data) {
                        Adhkar::$approving = true;
                        $record->update(['status' => 'approved']);
                        Adhkar::$approving = false;
                        ContentAuditLog::record($record, 'approved', null, ['note' => $data['note']]);
                        Notification::make()->title('Approved')->success()->send();
                    }),
                Tables\Actions\Action::make('return')->label('Return to draft')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(fn (Adhkar $r) => R::is(R::REVIEWER) && $r->status !== 'draft')
                    ->form([Forms\Components\Textarea::make('note')->label('What needs fixing?')->required()])
                    ->action(function (Adhkar $record, array $data) {
                        $record->update(['status' => 'draft']);
                        ContentAuditLog::record($record, 'returned', null, ['note' => $data['note']]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approveSelected')->label('Approve selected')->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn () => R::is(R::REVIEWER))
                    ->requiresConfirmation()
                    ->modalDescription('Confirm you have checked the Arabic text, translation(s), repetitions and reference of every selected item.')
                    ->form([Forms\Components\Textarea::make('note')->label('Review note')->required()])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $n = 0;
                        Adhkar::$approving = true;
                        try {
                            foreach ($records as $record) {
                                if ($record->status === 'approved') continue;
                                $record->update(['status' => 'approved']);
                                ContentAuditLog::record($record, 'approved', null, ['note' => $data['note']]);
                                $n++;
                            }
                        } finally {
                            Adhkar::$approving = false;
                        }
                        Notification::make()->title("{$n} approved")->success()->send();
                    }),
            ])
            ->defaultSort('sort')
            ->reorderable('sort');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAdhkar::route('/')];
    }
}
