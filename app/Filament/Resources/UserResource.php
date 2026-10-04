<?php
// ===== QURBA admin: users + roles =====
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Users & activity';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool { return R::is(R::SUPPORT); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return R::is() && $record->id !== auth()->id(); }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('role')->label('Admin role')->options(R::LABELS)->placeholder('Normal user (no admin access)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge()->formatStateUsing(fn (?string $s) => R::LABELS[$s] ?? '—')->placeholder('—'),
                Tables\Columns\TextColumn::make('devices')->label('Devices')
                    ->state(fn (User $u) => DB::table('user_devices')->where('user_id', $u->id)->count()),
                Tables\Columns\TextColumn::make('last_sync')->label('Last sync')
                    ->state(fn (User $u) => DB::table('sync_logs')->where('user_id', $u->id)->max('synced_at'))->since()->placeholder('never'),
                Tables\Columns\TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('role')->label('Admins')->nullable()])
            ->actions([Tables\Actions\EditAction::make()->label('Role')])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
