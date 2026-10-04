<?php
// ===== QURBA admin: interest list (people asking to join classes) =====
namespace App\Filament\Resources;

use App\Filament\Resources\LearnInterestResource\Pages;
use App\Models\LearnInterest;
use App\Support\AdminRoles as R;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LearnInterestResource extends Resource
{
    protected static ?string $model = LearnInterest::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Qurba Learning';
    protected static ?string $navigationLabel = 'Interest requests';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool { return R::is(R::SUPPORT); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return R::is(R::SUPPORT); }
    public static function canDelete(Model $record): bool { return R::is(); }

    public static function getNavigationBadge(): ?string
    {
        $n = LearnInterest::where('status', 'new')->count();
        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')->options(['new' => 'New', 'contacted' => 'Contacted', 'enrolled' => 'Enrolled', 'closed' => 'Closed'])->required(),
            Forms\Components\Textarea::make('staff_notes')->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
                Tables\Columns\TextColumn::make('course.title.en')->label('Course'),
                Tables\Columns\TextColumn::make('name')->searchable()->description(fn ($r) => $r->email),
                Tables\Columns\TextColumn::make('phone')->placeholder('—'),
                Tables\Columns\TextColumn::make('country')->placeholder('—'),
                Tables\Columns\TextColumn::make('format'),
                Tables\Columns\TextColumn::make('child_age_range')->label('Child')->placeholder('adult')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $s) => match ($s) { 'new' => 'warning', 'enrolled' => 'success', 'closed' => 'gray', default => 'info' }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['new' => 'New', 'contacted' => 'Contacted', 'enrolled' => 'Enrolled', 'closed' => 'Closed']),
                Tables\Filters\SelectFilter::make('course_id')->relationship('course', 'slug')->label('Course'),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Update'), Tables\Actions\ViewAction::make()->infolist(fn ($infolist) => $infolist->schema([
                \Filament\Infolists\Components\TextEntry::make('message')->placeholder('—'),
                \Filament\Infolists\Components\TextEntry::make('language'),
                \Filament\Infolists\Components\TextEntry::make('staff_notes')->placeholder('—'),
            ]))])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLearnInterests::route('/')];
    }
}
