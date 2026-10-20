<?php

namespace App\Filament\Artista\Resources\Artworks;

use App\Filament\Artista\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Artista\Resources\Artworks\Pages\EditArtwork;
use App\Filament\Artista\Resources\Artworks\Pages\ListArtworks;
use App\Filament\RelationManagers\ArtworkDocumentsRelationManager;
use App\Filament\RelationManagers\ArtworkImagesRelationManager;
use App\Filament\Schemas\ArtworkFields;
use App\Filament\Tables\ArtworksTable;
use App\Models\Artwork;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Obras del artista que inició sesión (§7, §15). */
class ArtworkResource extends Resource
{
    protected static ?string $model = Artwork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'obras';

    protected static ?string $modelLabel = 'obra';

    /** En español solo va mayúscula inicial: «Crear obra», no «Crear Obra». */
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Mis obras';

    protected static ?string $navigationLabel = 'Mis obras';

    protected static ?int $navigationSort = 2;

    /** Además de la policy, la consulta solo trae las obras del propio artista. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('artist_id', auth()->user()?->artist?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(ArtworkFields::sections(forAdmin: false));
    }

    public static function table(Table $table): Table
    {
        return ArtworksTable::configure($table, forAdmin: false);
    }

    public static function getRelations(): array
    {
        return [
            ArtworkImagesRelationManager::class,
            ArtworkDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArtworks::route('/'),
            'create' => CreateArtwork::route('/create'),
            'edit' => EditArtwork::route('/{record}/edit'),
        ];
    }
}
