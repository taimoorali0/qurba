<?php
// ===== QURBA admin: latest content changes =====
namespace App\Filament\Widgets;

use App\Models\ContentAuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivity extends TableWidget
{
    protected static ?string $heading = 'Recent activity';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->query(ContentAuditLog::query()->with('user')->latest('created_at'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()->color('gray'),
                Tables\Columns\TextColumn::make('action')->badge()->color(fn (string $state) => match ($state) {
                    'approved' => 'success', 'imported', 'created' => 'info', 'returned', 'deleted' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('auditable_type')->label('Item')
                    ->formatStateUsing(fn ($state, $record) => class_basename($state) . ' #' . $record->auditable_id),
                Tables\Columns\TextColumn::make('user.name')->label('By')->placeholder('System'),
            ]);
    }
}
