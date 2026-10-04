<?php
// ===== QURBA admin: Learning courses =====
namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Qurba Learning';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool { return R::is(R::CONTENT, R::SUPPORT); }
    public static function canCreate(): bool { return R::is(R::CONTENT); }
    public static function canEdit(Model $record): bool { return R::is(R::CONTENT); }
    public static function canDelete(Model $record): bool { return R::is(); }

    public static function form(Form $form): Form
    {
        $lang = fn (string $field, string $label, bool $area = false) => Forms\Components\Fieldset::make($label)->schema([
            ($area ? Forms\Components\Textarea::make("{$field}.en")->rows(2) : Forms\Components\TextInput::make("{$field}.en"))->label('English')->required(),
            ($area ? Forms\Components\Textarea::make("{$field}.ar")->rows(2) : Forms\Components\TextInput::make("{$field}.ar"))->label('Arabic')->extraInputAttributes(['dir' => 'rtl']),
            ($area ? Forms\Components\Textarea::make("{$field}.ur")->rows(2) : Forms\Components\TextInput::make("{$field}.ur"))->label('Urdu')->extraInputAttributes(['dir' => 'rtl']),
        ])->columns(3);

        return $form->schema([
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->alphaDash(),
            Forms\Components\Select::make('category')->options(['quran' => 'Quran', 'tajweed' => 'Tajweed', 'hifz' => 'Hifz', 'arabic' => 'Arabic'])->required(),
            Forms\Components\Select::make('audience')->options(['all' => 'Everyone', 'kids' => 'Children', 'adults' => 'Adults'])->required(),
            Forms\Components\Select::make('format')->options(['both' => '1-to-1 and group', 'one_to_one' => '1-to-1 only', 'group' => 'Group only'])->required(),
            Forms\Components\TextInput::make('min_age')->placeholder('e.g. 5+'),
            Forms\Components\TextInput::make('sort')->numeric()->default(0),
            Forms\Components\Toggle::make('active')->default(true),
            $lang('title', 'Title')->columnSpanFull(),
            $lang('summary', 'Short description', true)->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title.en')->label('Course')->searchable(),
            Tables\Columns\TextColumn::make('category')->badge(),
            Tables\Columns\TextColumn::make('audience'),
            Tables\Columns\TextColumn::make('interests_count')->counts('interests')->label('Interested'),
            Tables\Columns\IconColumn::make('active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()])->defaultSort('sort')->reorderable('sort');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCourses::route('/')];
    }
}
