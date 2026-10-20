<?php

namespace App\Filament\RelationManagers;

use App\Models\ArtworkImage;
use App\Support\Disks;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Galería de la obra (§8): subir, ordenar, eliminar y elegir la imagen principal. */
class ArtworkImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Imágenes';

    protected static ?string $modelLabel = 'imagen';

    protected static ?string $pluralModelLabel = 'imágenes';

    /** Se edita junto con la obra: en la vista, o si la obra está en revisión o publicada (para el artista), es solo lectura. */
    public function isReadOnly(): bool
    {
        return parent::isReadOnly() || ! auth()->user()?->can('update', $this->getOwnerRecord());
    }

    /** Tipos que se eligen a mano; «principal» se asigna con su propia acción. */
    private static function kindOptions(): array
    {
        return array_diff_key(ArtworkImage::KIND_LABELS, ['principal' => true]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')
                ->label('Tipo de imagen')
                ->options(self::kindOptions())
                ->default('adicional')
                ->required()
                ->hidden(fn (?ArtworkImage $record) => $record?->is_primary),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->getStateUsing(fn (ArtworkImage $record) => Disks::absoluteUrl($record->url))
                    ->imageHeight(64),
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ArtworkImage::KIND_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'principal' ? 'primary' : 'gray'),
                IconColumn::make('is_primary')
                    ->label('Principal')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Subir imágenes')
                    ->modalHeading('Subir imágenes')
                    ->schema([
                        FileUpload::make('paths')
                            ->label('Imágenes')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(20)
                            ->maxSize(8192)
                            ->disk(Disks::mediaName())
                            ->visibility('public')
                            ->directory(fn () => 'obras/'.$this->getOwnerRecord()->getKey())
                            ->required(),
                        Select::make('kind')
                            ->label('Tipo de imagen')
                            ->options(self::kindOptions())
                            ->default('adicional')
                            ->required(),
                    ])
                    ->using(function (array $data): ArtworkImage {
                        $images = collect($data['paths'])->map(fn (string $path) => $this->getOwnerRecord()
                            ->images()
                            ->create(['path' => $path, 'kind' => $data['kind']]));

                        return $images->first();
                    }),
            ])
            ->recordActions([
                Action::make('makePrimary')
                    ->label('Usar como principal')
                    ->icon(Heroicon::OutlinedStar)
                    ->visible(fn (ArtworkImage $record) => ! $record->is_primary && ! $this->isReadOnly())
                    ->action(fn (ArtworkImage $record) => $record->makePrimary()),
                EditAction::make()
                    ->visible(fn (ArtworkImage $record) => ! $record->is_primary),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin imágenes')
            ->emptyStateDescription('Sube la imagen principal y, si las tienes, fotos de la parte posterior, detalles y firma.');
    }
}
