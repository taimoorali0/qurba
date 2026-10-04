<?php
// ===== QURBA admin: content sources (licence + religious review workflow) =====
namespace App\Filament\Resources;

use App\Filament\Resources\ContentSourceResource\Pages;
use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContentSourceResource extends Resource
{
    protected static ?string $model = ContentSource::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Religious content';
    protected static ?string $navigationLabel = 'Sources & licences';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool { return R::is(R::CONTENT, R::REVIEWER); }
    public static function canCreate(): bool { return R::is(); }
    public static function canEdit(Model $record): bool { return R::is(R::CONTENT); }
    public static function canDelete(Model $record): bool { return false; }

    public static function getNavigationBadge(): ?string
    {
        $n = ContentSource::where('status', 'pending_review')->count();
        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->options(['quran_text' => 'Quran text', 'translation' => 'Translation', 'audio' => 'Audio', 'adhkar' => 'Adhkar', 'tafsir' => 'Tafsir', 'other' => 'Other'])->required(),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('edition')->maxLength(255),
            Forms\Components\TextInput::make('language_code')->maxLength(10),
            Forms\Components\TextInput::make('author')->label('Author / translator / reciter')->maxLength(255),
            Forms\Components\TextInput::make('source_url')->url()->maxLength(255),
            Forms\Components\TextInput::make('license')->maxLength(255),
            Forms\Components\Textarea::make('attribution')->rows(3)->columnSpanFull(),
            Forms\Components\Toggle::make('redistribution_allowed')->label('Redistribution permitted'),
            Forms\Components\Toggle::make('offline_allowed')->label('Offline download permitted'),
            Forms\Components\Textarea::make('review_notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->description(fn ($r) => $r->edition),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('language_code')->label('Lang'),
                Tables\Columns\IconColumn::make('redistribution_allowed')->label('Redistribute')->boolean(),
                Tables\Columns\IconColumn::make('offline_allowed')->label('Offline')->boolean(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'approved' => 'success', 'rejected' => 'danger', 'retired' => 'gray', default => 'warning',
                }),
                Tables\Columns\TextColumn::make('reviewed_at')->dateTime()->since()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['pending_review' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'retired' => 'Retired']),
                Tables\Filters\SelectFilter::make('type')->options(['quran_text' => 'Quran text', 'translation' => 'Translation', 'audio' => 'Audio', 'adhkar' => 'Adhkar', 'tafsir' => 'Tafsir', 'other' => 'Other']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn (ContentSource $record) => ContentAuditLog::record($record, 'updated', null, $record->getChanges())),
                self::decision('approve', 'Approve', 'approved', 'success', 'heroicon-o-check-badge'),
                self::decision('reject', 'Reject', 'rejected', 'danger', 'heroicon-o-x-circle'),
            ])
            ->defaultSort('status');
    }

    /** Only reviewers (and super admins) can change a source's religious status. */
    private static function decision(string $name, string $label, string $status, string $color, string $icon): Tables\Actions\Action
    {
        return Tables\Actions\Action::make($name)->label($label)->color($color)->icon($icon)
            ->visible(fn (ContentSource $r) => R::is(R::REVIEWER) && $r->status !== $status)
            ->requiresConfirmation()
            ->modalDescription($status === 'approved'
                ? 'Confirm you have verified accuracy, attribution and licence/permission. Approved content becomes visible in production.'
                : 'Rejected content is hidden in production.')
            ->form([Forms\Components\Textarea::make('note')->label('Review note')->required()->rows(3)])
            ->action(function (ContentSource $record, array $data) use ($status) {
                $old = $record->status;
                $record->update(['status' => $status, 'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
                    'review_notes' => trim(($record->review_notes ? $record->review_notes . "\n" : '') . now()->toDateString() . ' ' . auth()->user()->name . ': ' . $data['note'])]);
                ContentAuditLog::record($record, $status, ['status' => $old], ['status' => $status, 'note' => $data['note']]);
                Notification::make()->title("Source {$status}")->success()->send();
            });
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContentSources::route('/')];
    }
}
