<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentEntryResource\Pages;
use App\Models\ContentEntry;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContentEntryResource extends Resource
{
    protected static ?string $model = ContentEntry::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Content library';

    public static function canViewAny(): bool { return R::is(R::CONTENT); }
    public static function canCreate(): bool { return R::is(R::CONTENT); }
    public static function canEdit(Model $record): bool { return R::is(R::CONTENT); }
    public static function canDelete(Model $record): bool { return R::is(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('module')->options(config('content.modules'))->required(),
            Forms\Components\TextInput::make('slug')->required()->regex('/^[a-z0-9-]+$/')->maxLength(255),
            Forms\Components\TextInput::make('collection')->maxLength(255),
            Forms\Components\TextInput::make('chapter')->maxLength(255),
            Forms\Components\TextInput::make('reference')->maxLength(255),
            Forms\Components\TextInput::make('grading')->maxLength(255),
            Forms\Components\Select::make('content_source_id')->relationship('source', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'review' => 'In review', 'published' => 'Published'])->default('draft')->required()
                ->helperText('Public API also requires an approved source with redistribution permission.'),
            Forms\Components\TextInput::make('sort')->numeric()->minValue(0)->default(0),
            ...collect(['en' => 'English', 'ar' => 'Arabic', 'ur' => 'Urdu'])->map(fn ($label, $lang) => Forms\Components\Fieldset::make($label)->schema([
                Forms\Components\TextInput::make("title.{$lang}")->required($lang === 'en')->maxLength(255),
                Forms\Components\Textarea::make("body.{$lang}")->rows(8)->required($lang === 'en')
                    ->extraInputAttributes(['dir' => $lang === 'en' ? 'ltr' : 'rtl']),
            ]))->values()->all(),
            Forms\Components\Repeater::make('recordings')->relationship()->schema([
                Forms\Components\TextInput::make('speaker')->required()->maxLength(255),
                Forms\Components\Select::make('language_code')->options(['ar' => 'Arabic', 'ur' => 'Urdu', 'en' => 'English'])->required(),
                Forms\Components\TextInput::make('url')->url()->required()->maxLength(2048),
                Forms\Components\TextInput::make('duration_seconds')->numeric()->minValue(1),
                Forms\Components\Select::make('content_source_id')->relationship('source', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->default('draft')->required(),
            ])->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title.en')->label('Title')->searchable(),
            Tables\Columns\TextColumn::make('module')->badge(),
            Tables\Columns\TextColumn::make('collection'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('source.name')->label('Source'),
        ])->filters([
            Tables\Filters\SelectFilter::make('module')->options(config('content.modules')),
        ])->actions([Tables\Actions\EditAction::make()])->defaultSort('sort');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContentEntries::route('/')];
    }
}
