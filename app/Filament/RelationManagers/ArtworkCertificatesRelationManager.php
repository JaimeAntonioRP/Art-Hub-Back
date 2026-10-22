<?php

namespace App\Filament\RelationManagers;

use App\Models\Artwork;
use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

/**
 * Certificados de la obra (§19–§20). Solo el admin emite, reemite y anula;
 * el artista ve el historial de certificados y descarga el PDF.
 */
class ArtworkCertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Certificados';

    protected static ?string $modelLabel = 'certificado';

    protected static ?string $pluralModelLabel = 'certificados';

    public function isReadOnly(): bool
    {
        return true; // los certificados no se editan: se emiten, se reemplazan o se anulan
    }

    private static function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('issuer'))
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->weight('medium')
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Certificate::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Certificate::STATUS_VIGENTE => 'success',
                        Certificate::STATUS_ANULADO => 'danger',
                        default => 'gray',
                    })
                    ->description(fn (Certificate $record) => $record->revoke_reason
                        ? str($record->revoke_reason)->limit(60)
                        : ($record->replaced_by_id ? 'Reemplazado por '.$record->replacedBy?->number : null)),
                TextColumn::make('data_check')
                    ->label('Datos')
                    ->badge()
                    ->getStateUsing(fn (Certificate $record) => $record->isVigente()
                        ? ($record->matchesCurrentArtwork() ? 'Al día' : 'La obra cambió')
                        : null)
                    ->color(fn (?string $state) => $state === 'Al día' ? 'success' : 'warning')
                    ->tooltip(fn (?string $state) => $state === 'La obra cambió'
                        ? 'La ficha, la imagen principal o un documento relevante cambió desde la emisión: conviene reemitir.'
                        : null)
                    ->placeholder('—'),
                TextColumn::make('issued_at')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (Certificate $record) => $record->issuer?->name),
                TextColumn::make('pdf_sha256')
                    ->label('Huella del PDF')
                    ->formatStateUsing(fn (?string $state) => $state ? substr($state, 0, 12).'…' : '—')
                    ->fontFamily('mono')
                    ->copyable()
                    ->tooltip('Copiar la huella completa')
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('issue')
                    ->label(fn () => $this->hasVigente() ? 'Reemitir certificado' : 'Emitir certificado')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->visible(fn () => self::isAdmin())
                    ->disabled(fn () => ! $this->canIssue())
                    ->tooltip(fn () => $this->canIssue() ? null : 'La obra debe estar aprobada o publicada.')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => $this->hasVigente() ? 'Reemitir el certificado' : 'Emitir el certificado')
                    ->modalDescription(fn () => ($this->hasVigente()
                        ? 'El certificado vigente quedará como «Reemplazado» y el portal remitirá al nuevo. '
                        : '')
                        .'El PDF certifica la ficha actual, la imagen principal y los documentos marcados como relevantes.')
                    ->modalSubmitActionLabel('Emitir')
                    ->action(function () {
                        try {
                            $certificate = Certificate::issueFor($this->getOwnerRecord(), auth()->user());
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title('No se pudo emitir el certificado')->body($e->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title("Certificado {$certificate->number} emitido")->send();
                    }),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Certificate $record) => $record->pdf_path ? url("/api/certificates/{$record->verification_token}/pdf") : null)
                    ->openUrlInNewTab(),
                Action::make('portal')
                    ->label('Ver en el portal')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Certificate $record) => $record->verificationUrl())
                    ->openUrlInNewTab(),
                Action::make('revoke')
                    ->label('Anular')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (Certificate $record) => self::isAdmin() && $record->isVigente())
                    ->modalHeading(fn (Certificate $record) => "Anular el certificado {$record->number}")
                    ->modalDescription('El portal mostrará el certificado como anulado, con este motivo. No se puede deshacer; para volver a certificar la obra, emite uno nuevo.')
                    ->modalSubmitActionLabel('Anular certificado')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Motivo (se muestra en el portal)')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (Certificate $record, array $data) {
                        $record->revoke(auth()->user(), $data['reason']);
                        Notification::make()->success()->title("Certificado {$record->number} anulado")->send();
                    }),
            ])
            ->emptyStateHeading('Sin certificado')
            ->emptyStateDescription(fn () => self::isAdmin()
                ? 'Cuando la obra esté aprobada o publicada, emite su certificado con QR.'
                : 'ArtHub emite el certificado después de aprobar la obra.');
    }

    private function hasVigente(): bool
    {
        return $this->getOwnerRecord()->certificates()->where('status', Certificate::STATUS_VIGENTE)->exists();
    }

    private function canIssue(): bool
    {
        /** @var Artwork $artwork */
        $artwork = $this->getOwnerRecord();

        return in_array($artwork->status, Certificate::ISSUABLE_ARTWORK_STATUSES, true);
    }
}
