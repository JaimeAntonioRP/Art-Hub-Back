<?php

namespace App\Filament\Resources\Artists\Schemas;

use App\Filament\Schemas\ArtistProfileFields;
use App\Models\Artist;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArtistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Publicación')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => Artist::STATUS_LABELS[$state] ?? $state)
                            ->color(fn (string $state) => ArtistStatus::color($state))
                            ->hiddenOn('create'),
                        TextEntry::make('user.email')
                            ->label('Cuenta')
                            ->placeholder('Sin cuenta (perfil cargado por ArtHub)')
                            ->hiddenOn('create'),
                        Toggle::make('featured')
                            ->label('Destacado en el sitio'),
                        TextInput::make('slug')
                            ->label('Dirección en el sitio')
                            ->prefix('/artistas/')
                            ->helperText('Se genera a partir del nombre si la dejas vacía.')
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('birth_year')
                            ->label('Año de nacimiento')
                            ->numeric()
                            ->minValue(1500)
                            ->maxValue(2100),
                        TextInput::make('death_year')
                            ->label('Año de fallecimiento')
                            ->numeric()
                            ->minValue(1500)
                            ->maxValue(2100),
                    ]),
                ...ArtistProfileFields::sections(),
            ]);
    }
}
