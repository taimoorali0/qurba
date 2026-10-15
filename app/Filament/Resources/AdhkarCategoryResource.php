<?php
// ===== QURBA admin: adhkar categories — names and the read-along recording =====
namespace App\Filament\Resources;

use App\Filament\Resources\AdhkarCategoryResource\Pages;
use App\Models\AdhkarCategory;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdhkarCategoryResource extends Resource
{
    protected static ?string $model = AdhkarCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationGroup = 'Religious content';
    protected static ?string $navigationLabel = 'Adhkar categories & audio';
    protected static ?string $modelLabel = 'adhkar category';
    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool { return R::is(R::CONTENT); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return R::is(R::CONTENT); }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name.en')->label('Name (English)')->required()->maxLength(60),
            Forms\Components\TextInput::make('name.ur')->label('Name (Urdu)')->maxLength(60)->extraInputAttributes(['dir' => 'rtl']),
            Forms\Components\TextInput::make('name.ar')->label('Name (Arabic)')->maxLength(60)->extraInputAttributes(['dir' => 'rtl']),
            Forms\Components\FileUpload::make('audio_url')->label('Read-along recording (optional)')->columnSpanFull()
                ->disk('audio')->directory('adhkar-categories')->visibility('public')
                ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])->maxSize(30 * 1024)
                ->helperText('One MP3 reciting the duas of this category in order. Then open each dua and set "Starts at" so the app highlights it while it is recited. Only upload recordings you have permission to publish.'),
            Forms\Components\TextInput::make('audio_credit')->label('Reciter / credit')->placeholder('e.g. Mishary Rashid Alafasy')->maxLength(120),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Category')->formatStateUsing(fn ($state, AdhkarCategory $r) => $r->label()),
                Tables\Columns\TextColumn::make('slug')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('adhkar_count')->counts('adhkar')->label('Duas'),
                Tables\Columns\IconColumn::make('audio_url')->label('Recording')->boolean()->getStateUsing(fn (AdhkarCategory $r) => (bool) $r->audio_url),
                Tables\Columns\TextColumn::make('audio_credit')->label('Reciter')->placeholder('—'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAdhkarCategories::route('/')];
    }
}
