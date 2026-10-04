<?php
// ===== QURBA admin: reciters + audio provider =====
namespace App\Filament\Resources;

use App\Filament\Resources\QuranReciterResource\Pages;
use App\Models\ContentAuditLog;
use App\Models\QuranReciter;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuranReciterResource extends Resource
{
    protected static ?string $model = QuranReciter::class;
    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';
    protected static ?string $navigationGroup = 'Religious content';
    protected static ?string $navigationLabel = 'Reciters & audio';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool { return R::is(R::CONTENT, R::REVIEWER); }
    public static function canCreate(): bool { return R::is(R::CONTENT); }
    public static function canEdit(Model $record): bool { return R::is(R::CONTENT); }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name_arabic'),
            Forms\Components\TextInput::make('style'),
            Forms\Components\TextInput::make('ayah_url_pattern')->label('Per-ayah URL pattern')->columnSpanFull()
                ->helperText('Use {sss}{aaa} for zero-padded surah/ayah (001001) or {s}/{a} for plain numbers.'),
            Forms\Components\Select::make('content_source_id')->label('Audio source / licence')
                ->relationship('source', 'name', fn ($q) => $q->where('type', 'audio'))->searchable()->preload(),
            Forms\Components\TextInput::make('sort')->numeric()->default(0),
            Forms\Components\Toggle::make('active')->helperText('Production also requires the source to be approved.'),
            Forms\Components\Toggle::make('offline_allowed')->label('Offline download permitted'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('source.name')->label('Source')->placeholder('—'),
                Tables\Columns\TextColumn::make('source.status')->label('Licence')->badge()
                    ->color(fn (?string $state) => $state === 'approved' ? 'success' : 'warning')->placeholder('none'),
                Tables\Columns\IconColumn::make('active')->boolean(),
                Tables\Columns\IconColumn::make('offline_allowed')->label('Offline')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()->after(fn (QuranReciter $r) => ContentAuditLog::record($r, 'updated', null, $r->getChanges()))])
            ->defaultSort('sort');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageQuranReciters::route('/')];
    }
}
