<?php

namespace App\Filament\RelationManagers;

use App\Models\Document;
use App\Models\DocumentVersion;
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
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
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
            ->modifyQueryUsing(fn ($query) => $query->with('latestVersion')->withCount('versions'))
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
                TextColumn::make('versions_count')
                    ->label('Versión')
                    ->formatStateUsing(fn (int $state) => "v{$state}")
                    ->alignCenter(),
                TextColumn::make('integrity')
                    ->label('Integridad')
                    ->badge()
                    ->getStateUsing(fn (Document $record) => match ($record->latestVersion?->verification_ok) {
                        true => 'Íntegro',
                        false => 'Alterado',
                        default => 'Sin comprobar',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Íntegro' => 'success',
                        'Alterado' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (Document $record) => $record->latestVersion?->verified_at
                        ? 'Comprobado el '.$record->latestVersion->verified_at->format('d/m/Y H:i')
                        : null),
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
                Action::make('verifyAll')
                    ->label('Comprobar integridad')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->isAdmin())
                    ->requiresConfirmation()
                    ->modalHeading('Comprobar la integridad del expediente')
                    ->modalDescription('Se vuelve a calcular la huella de cada versión guardada y se compara con la registrada al subirla.')
                    ->modalSubmitActionLabel('Comprobar')
                    ->action(fn () => self::notifyIntegrity(
                        DocumentVersion::whereIn('document_id', $this->getOwnerRecord()->documents()->select('id'))->get(),
                    )),
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
                Action::make('newVersion')
                    ->label('Nueva versión')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn () => ! $this->isReadOnly())
                    ->modalHeading(fn (Document $record) => "Nueva versión de «{$record->title}»")
                    ->modalDescription('La versión anterior se conserva con su huella. El cambio queda en el historial de la obra.')
                    ->modalSubmitActionLabel('Guardar versión')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Archivo')
                            ->disk(Disks::documentsName())
                            ->visibility('private')
                            ->directory(fn () => 'expedientes/'.$this->getOwnerRecord()->getKey())
                            ->acceptedFileTypes(Document::ACCEPTED_MIME_TYPES)
                            ->maxSize(Document::MAX_KB)
                            ->storeFileNamesIn('original_name')
                            ->required(),
                        Hidden::make('original_name'),
                        Textarea::make('reason')
                            ->label('Motivo del cambio')
                            ->placeholder('Por ejemplo: se corrigió la fecha del certificado de procedencia.')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->action(function (Document $record, array $data) {
                        $record->addVersion($data['file'], $data['original_name'] ?? basename($data['file']), auth()->user(), $data['reason']);
                        Notification::make()->success()->title('Nueva versión guardada')->send();
                    }),
                Action::make('versions')
                    ->label('Versiones')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading(fn (Document $record) => "Versiones de «{$record->title}»")
                    ->modalContent(fn (Document $record) => view('filament.document-versions', [
                        'versions' => $record->versions()->with('uploader')->get()->sortByDesc('version'),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions(fn (Document $record) => auth()->user()?->isAdmin() ? [
                        Action::make('verifyVersions')
                            ->label('Comprobar integridad')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->action(fn () => self::notifyIntegrity($record->versions)),
                    ] : []),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Expediente vacío')
            ->emptyStateDescription('Agrega declaraciones, certificados existentes, documentos de procedencia, informes o peritajes.');
    }

    /** Comprueba las versiones dadas y avisa del resultado (§22). */
    private static function notifyIntegrity(Collection $versions): void
    {
        if ($versions->isEmpty()) {
            Notification::make()->title('No hay archivos que comprobar')->send();

            return;
        }

        $failed = $versions->reject(fn (DocumentVersion $v) => $v->verifyIntegrity());

        if ($failed->isEmpty()) {
            Notification::make()->success()
                ->title('Integridad verificada')
                ->body($versions->count() === 1 ? 'El archivo coincide con su huella.' : "Los {$versions->count()} archivos coinciden con su huella.")
                ->send();

            return;
        }

        Notification::make()->danger()->persistent()
            ->title('Integridad no verificada')
            ->body($failed->map(fn (DocumentVersion $v) => "«{$v->document->title}» v{$v->version}")->implode(', ').' no coincide con su huella. Quedó registrado en el historial.')
            ->send();
    }
}
