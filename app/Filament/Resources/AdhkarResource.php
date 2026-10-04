<?php
// ===== QURBA admin: adhkar & duas with draft → in review → approved workflow =====
namespace App\Filament\Resources;

use App\Filament\Resources\AdhkarResource\Pages;
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Models\ContentAuditLog;
use App\Support\AdminRoles as R;
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
