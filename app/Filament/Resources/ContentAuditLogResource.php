<?php
// ===== QURBA admin: religious content audit trail (read-only) =====
namespace App\Filament\Resources;

use App\Filament\Resources\ContentAuditLogResource\Pages;
use App\Models\ContentAuditLog;
use App\Support\AdminRoles as R;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContentAuditLogResource extends Resource
{
    protected static ?string $model = ContentAuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Users & activity';
    protected static ?string $navigationLabel = 'Audit trail';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool { return R::is(R::REVIEWER); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('action')->badge(),
                Tables\Columns\TextColumn::make('auditable_type')->label('Item')->formatStateUsing(fn ($s, $r) => class_basename($s) . ' #' . $r->auditable_id),
                Tables\Columns\TextColumn::make('user.name')->label('By')->placeholder('system / importer'),
                Tables\Columns\TextColumn::make('new_values')->label('Change')->formatStateUsing(fn ($s) => mb_substr(json_encode($s, JSON_UNESCAPED_UNICODE), 0, 160))->wrap(),
                Tables\Columns\TextColumn::make('ip')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([Tables\Filters\SelectFilter::make('action')->options(fn () => ContentAuditLog::distinct()->pluck('action', 'action'))])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContentAuditLogs::route('/')];
    }
}
