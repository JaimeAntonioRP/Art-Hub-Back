<?php

namespace App\Filament\Resources\CommercialRequests;

use App\Filament\Resources\Artworks\ArtworkResource;
use App\Filament\Resources\CommercialRequests\Pages\EditCommercialRequest;
use App\Filament\Resources\CommercialRequests\Pages\ListCommercialRequests;
use App\Models\CommercialRequest;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Solicitudes comerciales (§14). Se gestionan fuera de la plataforma; aquí se registra su estado. */
class CommercialRequestResource extends Resource
{
    protected static ?string $model = CommercialRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $slug = 'solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    /** En español solo va mayúscula inicial. */
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // llegan desde la ficha pública de cada obra
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Solicitud')
                ->columns(3)
                ->schema([
                    TextEntry::make('artwork.title')
                        ->label('Obra')
                        ->url(fn (CommercialRequest $record) => ArtworkResource::getUrl('edit', ['record' => $record->artwork_id])),
                    TextEntry::make('artwork.code')->label('Código'),
                    TextEntry::make('type')->label('Tipo')->badge()
                        ->formatStateUsing(fn (string $state) => CommercialRequest::TYPE_LABELS[$state] ?? $state),
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('email')->label('Correo')->copyable(),
                    TextEntry::make('phone')->label('Teléfono')->placeholder('—')->copyable(),
                    TextEntry::make('message')->label('Mensaje')->placeholder('Sin mensaje')->columnSpanFull(),
                    TextEntry::make('created_at')->label('Recibida')->dateTime('d/m/Y H:i'),
                ]),
            Section::make('Gestión')
                ->schema([
                    Select::make('status')
                        ->label('Estado')
                        ->options(CommercialRequest::STATUS_LABELS)
                        ->required(),
                    Textarea::make('admin_notes')
                        ->label('Notas internas')
                        ->helperText('Solo las ve el equipo de ArtHub.')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('artwork'))
            ->columns([
                TextColumn::make('created_at')->label('Recibida')->since()->sortable(),
                TextColumn::make('artwork.title')->label('Obra')->description(fn (CommercialRequest $record) => $record->artwork?->code)->searchable(),
                TextColumn::make('name')->label('Nombre')->description(fn (CommercialRequest $record) => $record->email)->searchable(['name', 'email']),
                TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state) => CommercialRequest::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'intencion_compra' ? 'success' : 'gray'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => CommercialRequest::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'nueva' => 'info',
                        'contactado', 'en_evaluacion' => 'warning',
                        'confirmada' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(CommercialRequest::STATUS_LABELS),
                SelectFilter::make('type')->label('Tipo')->options(CommercialRequest::TYPE_LABELS),
            ])
            ->recordActions([
                EditAction::make()->label('Gestionar'),
            ])
            ->emptyStateHeading('Todavía no hay solicitudes');
    }

    public static function getNavigationBadge(): ?string
    {
        $new = CommercialRequest::where('status', CommercialRequest::STATUS_NUEVA)->count();

        return $new ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommercialRequests::route('/'),
            'edit' => EditCommercialRequest::route('/{record}/edit'),
        ];
    }
}
