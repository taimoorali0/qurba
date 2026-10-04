<?php
// ===== QURBA admin: surah metadata (read-only) =====
namespace App\Filament\Resources;

use App\Filament\Resources\QuranSurahResource\Pages;
use App\Models\QuranSurah;
use App\Support\AdminRoles as R;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuranSurahResource extends Resource
{
    protected static ?string $model = QuranSurah::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Religious content';
    protected static ?string $navigationLabel = 'Surahs';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool { return R::is(R::CONTENT, R::REVIEWER); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('name_simple')->label('Name')->searchable(),
            Tables\Columns\TextColumn::make('name_arabic')->label('Arabic')->extraAttributes(['dir' => 'rtl', 'style' => "font-family:'Amiri Quran','Amiri',serif;font-size:1.2rem"]),
            Tables\Columns\TextColumn::make('name_english')->label('Meaning'),
            Tables\Columns\TextColumn::make('revelation_place')->badge(),
            Tables\Columns\TextColumn::make('ayah_count')->label('Ayahs'),
        ])->defaultSort('id')->paginated([25, 50, 114]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageQuranSurahs::route('/')];
    }
}
