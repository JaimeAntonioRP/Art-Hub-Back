<?php

namespace App\Filament\RelationManagers;

use App\Models\Document;
use App\Support\Disks;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Expediente documental (§9). Los archivos van al disco privado de documentos
 * y solo se descargan tras comprobar permisos (DocumentDownloadController).
 */
class ArtworkDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Expediente';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    /** Se edita junto con la obra: en la vista, o si la obra está en revisión o publicada (para el artista), es solo lectura. */
    public function isReadOnly(): bool
    {
        return parent::isReadOnly() || ! auth()->user()?->can('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')
                ->label('Tipo de documento')
                ->options(Document::CATEGORY_LABELS)
                ->required(),
            TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Descripción')
                ->rows(3)
                ->columnSpanFull(),
            Select::make('visibility')
                ->label('Visibilidad')
                ->options(['privado' => 'Privado (solo ArtHub y el artista)', 'publico' => 'Público (visible en la ficha)'])
                ->default('privado')
                ->required(),
            Toggle::make('is_relevant')
                ->label('Documento relevante')
                ->helperText('Se le generará huella y versiones en la certificación.')
                ->inline(false),
            FileUpload::make('file')
                ->label('Archivo')
                ->disk(Disks::documentsName())
                ->visibility('private')
                ->directory(fn () => 'expedientes/'.$this->getOwnerRecord()->getKey())
                ->acceptedFileTypes(Document::ACCEPTED_MIME_TYPES)
                ->maxSize(Document::MAX_KB)
                ->storeFileNamesIn('original_name')
                ->helperText('PDF, XML, imágenes o Word. Máximo 20 MB.')
                ->required()
                ->visibleOn('create')
                ->columnSpanFull(),
            Hidden::make('original_name')
                ->visibleOn('create'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('latestVersion'))
            ->columns([
                TextColumn::make('title')
                    ->label('Documento')
                    ->description(fn (Document $record) => $record->latestVersion?->original_name)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Document::CATEGORY_LABELS[$state] ?? $state),
                TextColumn::make('visibility')
                    ->label('Visibilidad')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'publico' ? 'Público' : 'Privado')
                    ->color(fn (string $state) => $state === 'publico' ? 'success' : 'gray'),
                IconColumn::make('is_relevant')
                    ->label('Relevante')
                    ->boolean(),
                TextColumn::make('latestVersion.size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn ($state) => $state ? Number::fileSize($state, precision: 1) : '—'),
                TextColumn::make('latestVersion.sha256')
                    ->label('Huella SHA-256')
                    ->formatStateUsing(fn (?string $state) => $state ? substr($state, 0, 12).'…' : '—')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyableState(fn (Document $record) => $record->latestVersion?->sha256)
                    ->tooltip('Copiar la huella completa')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Agregado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Tipo')
                    ->options(Document::CATEGORY_LABELS),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar documento')
                    ->using(function (array $data): Document {
                        return DB::transaction(function () use ($data) {
                            $document = $this->getOwnerRecord()->documents()->create([
                                ...collect($data)->except(['file', 'original_name'])->all(),
                                'created_by' => auth()->id(),
                            ]);
                            $document->addVersion($data['file'], $data['original_name'] ?? basename($data['file']), auth()->user());

                            return $document;
                        });
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Document $record) => $record->latestVersion
                        ? route('documents.download', $record->latestVersion)
                        : null)
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Expediente vacío')
            ->emptyStateDescription('Agrega declaraciones, certificados existentes, documentos de procedencia, informes o peritajes.');
    }
}
