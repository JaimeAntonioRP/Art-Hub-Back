<?php

namespace App\Filament\Schemas;

use App\Models\Artist;
use App\Support\Disks;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/** Campos del perfil de artista (§6), compartidos por el panel admin y el del artista. */
final class ArtistProfileFields
{
    /** @return array<\Filament\Schemas\Components\Component> */
    public static function sections(): array
    {
        return [
            Section::make('Identidad')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nombres y apellidos')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('artistic_name')
                        ->label('Nombre artístico')
                        ->maxLength(255),
                    // Las fotos cargadas antes del panel son URLs; las nuevas se suben aquí.
                    FileUpload::make('profile_image_url')
                        ->label('Fotografía')
                        ->image()
                        ->imageEditor()
                        ->disk(Disks::mediaName())
                        ->directory('artists')
                        ->maxSize(4096)
                        ->visible(fn (?Artist $record) => ! self::hasLegacyPhoto($record))
                        ->columnSpanFull(),
                    TextInput::make('profile_image_url')
                        ->label('Fotografía (URL)')
                        ->helperText('Esta foto se cargó antes del panel. Para cambiarla, borra la URL y guarda: aparecerá el campo para subir una nueva.')
                        ->maxLength(2048)
                        ->visible(fn (?Artist $record) => self::hasLegacyPhoto($record))
                        ->columnSpanFull(),
                    TextInput::make('origin')
                        ->label('Procedencia')
                        ->placeholder('Cusco, Perú')
                        ->maxLength(255),
                    TextInput::make('specialty')
                        ->label('Especialidad')
                        ->placeholder('Pintura al óleo')
                        ->maxLength(255),
                    TagsInput::make('techniques')
                        ->label('Técnicas')
                        ->placeholder('Agrega una técnica y pulsa Enter')
                        ->columnSpanFull(),
                ]),
            Section::make('Biografía y trayectoria')
                ->schema([
                    Textarea::make('bio')
                        ->label('Biografía')
                        ->rows(5),
                    Textarea::make('career')
                        ->label('Trayectoria')
                        ->helperText('Exposiciones, colecciones y formación.')
                        ->rows(4),
                    Textarea::make('awards')
                        ->label('Reconocimientos')
                        ->rows(3),
                ]),
            Section::make('Contacto')
                ->columns(2)
                ->schema([
                    TextInput::make('contact_email')
                        ->label('Correo de contacto')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('contact_phone')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(50),
                    KeyValue::make('social_links')
                        ->label('Redes sociales')
                        ->keyLabel('Red')
                        ->valueLabel('Enlace')
                        ->keyPlaceholder('Instagram')
                        ->valuePlaceholder('https://instagram.com/…')
                        ->columnSpanFull(),
                ]),
        ];
    }

    private static function hasLegacyPhoto(?Artist $record): bool
    {
        return (bool) preg_match('#^(https?://|/)#', (string) $record?->profile_image_url);
    }
}
