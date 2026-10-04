<?php
// ===== QURBA admin: Quran text (read-only — never editable here) =====
namespace App\Filament\Resources;

use App\Filament\Resources\QuranAyahResource\Pages;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Support\AdminRoles as R;
use App\Support\Quran\ArabicNormalizer;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuranAyahResource extends Resource
{
    protected static ?string $model = QuranAyah::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Religious content';
    protected static ?string $navigationLabel = 'Quran text';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool { return R::is(R::CONTENT, R::REVIEWER); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ayah_key')->label('Ayah')->sortable(query: fn (Builder $q, string $d) => $q->orderBy('id', $d)),
                Tables\Columns\TextColumn::make('surah.name_simple')->label('Surah'),
                Tables\Columns\TextColumn::make('text_uthmani')->label('Text')->wrap()
                    ->extraAttributes(['dir' => 'rtl', 'style' => "font-family:'Amiri Quran','Amiri',serif;font-size:1.35rem;line-height:2.2"])
                    ->searchable(query: fn (Builder $q, string $s) => $q->where('text_search', 'like', '%' . ArabicNormalizer::forSearch($s) . '%')),
                Tables\Columns\TextColumn::make('juz'),
                Tables\Columns\TextColumn::make('page'),
                Tables\Columns\TextColumn::make('content_hash')->label('Hash')->limit(10)->fontFamily('mono')->copyable(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('surah_id')->label('Surah')
                ->options(fn () => QuranSurah::orderBy('id')->get()->mapWithKeys(fn ($s) => [$s->id => "{$s->id}. {$s->name_simple}"]))])
            ->defaultSort('id')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageQuranAyahs::route('/')];
    }
}
