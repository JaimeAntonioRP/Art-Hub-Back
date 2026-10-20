<?php

namespace App\Filament\Resources\Artworks;

use App\Filament\RelationManagers\ArtworkDocumentsRelationManager;
use App\Filament\RelationManagers\ArtworkImagesRelationManager;
use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Resources\Artworks\Pages\EditArtwork;
use App\Filament\Resources\Artworks\Pages\ListArtworks;
use App\Filament\Schemas\ArtworkFields;
use App\Filament\Tables\ArtworksTable;
use App\Models\Artwork;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArtworkResource extends Resource
{
    protected static ?string $model = Artwork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'obras';

    protected static ?string $modelLabel = 'obra';

    /** En español solo va mayúscula inicial: «Crear obra», no «Crear Obra». */
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Obras';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(ArtworkFields::sections(forAdmin: true));
    }

    public static function table(Table $table): Table
    {
        return ArtworksTable::configure($table, forAdmin: true);
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
