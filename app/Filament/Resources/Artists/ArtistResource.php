<?php

namespace App\Filament\Resources\Artists;

use App\Filament\Resources\Artists\Pages\CreateArtist;
use App\Filament\Resources\Artists\Pages\EditArtist;
use App\Filament\Resources\Artists\Pages\ListArtists;
use App\Filament\Resources\Artists\RelationManagers\ReviewNotesRelationManager;
use App\Filament\Resources\Artists\Schemas\ArtistForm;
use App\Filament\Resources\Artists\Tables\ArtistsTable;
use App\Models\Artist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'artistas';

    protected static ?string $modelLabel = 'artista';

    protected static ?string $pluralModelLabel = 'artistas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ArtistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArtistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReviewNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArtists::route('/'),
            'create' => CreateArtist::route('/create'),
            'edit' => EditArtist::route('/{record}/edit'),
        ];
    }

    /** Perfiles pendientes de revisión, en el menú lateral. */
    public static function getNavigationBadge(): ?string
    {
        $pending = Artist::where('status', Artist::STATUS_PENDIENTE)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    /** Se desactivan, no se borran: las obras dependen del perfil. */
    public static function canDelete($record): bool
    {
        return false;
    }
}
