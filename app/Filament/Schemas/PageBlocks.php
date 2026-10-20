<?php

namespace App\Filament\Schemas;

use App\Models\Artwork;
use App\Support\Disks;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

/**
 * Bloques con los que se arman las páginas públicas. El frontend tiene un
 * componente por tipo (components/blocks). Cada imagen admite un archivo subido
 * o una URL/ruta; si hay archivo, gana el archivo.
 */
final class PageBlocks
{
    /** Íconos que el frontend sabe dibujar en las tarjetas. */
    public const ICONS = [
        'obra' => 'Obra',
        'artista' => 'Artista',
        'documento' => 'Documento',
        'revision' => 'Revisión',
        'verificar' => 'Verificación',
        'realidad' => 'Realidad aumentada',
        'catalogo' => 'Catálogo',
        'contacto' => 'Contacto',
    ];

    /** Campos de imagen: el archivo subido y su alternativa por URL. */
    private const IMAGE_KEYS = ['image', 'photo', 'poster'];

    /** @return array<Block> */
    public static function blocks(): array
    {
        return [
            Block::make('hero')
                ->label('Portada')
                ->icon(Heroicon::OutlinedSparkles)
                ->schema([
                    Select::make('variant')
                        ->label('Diseño')
                        ->options([
                            'grande' => 'Imagen a pantalla completa con el texto encima',
                            'dividida' => 'Texto a un lado e imagen al otro',
                        ])
                        // Opcional: las portadas creadas antes de este campo se ven «divididas».
                        ->default('grande')
                        ->placeholder('Texto a un lado e imagen al otro'),
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    Grid::make(2)->schema([
                        TextInput::make('title')->label('Título')->required(),
                        TextInput::make('highlight')->label('Parte destacada')->helperText('Se muestra en cursiva dorada, a continuación del título.'),
                    ]),
                    Textarea::make('text')->label('Texto')->rows(3),
                    ...self::image('image', 'Imagen', 'Para la portada grande: horizontal, de al menos 1920 px de ancho.'),
                    Toggle::make('show_featured_artwork')
                        ->label('Mostrar una obra publicada en lugar de la imagen'),
                    ...self::buttons(),
                ]),
            Block::make('text')
                ->label('Texto')
                ->icon(Heroicon::OutlinedBars3BottomLeft)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título'),
                    Textarea::make('body')->label('Texto')->rows(6)->helperText('Deja una línea en blanco entre párrafos.')->required(),
                ]),
            Block::make('features')
                ->label('Tarjetas')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título'),
                    Textarea::make('intro')->label('Introducción')->rows(2),
                    Repeater::make('items')
                        ->label('Tarjetas')
                        ->schema([
                            Select::make('icon')->label('Ícono')->options(self::ICONS),
                            TextInput::make('title')->label('Título')->required(),
                            Textarea::make('text')->label('Texto')->rows(2),
                        ])
                        ->columns(3)
                        ->minItems(1)
                        ->collapsible(),
                ]),
            Block::make('steps')
                ->label('Pasos')
                ->icon(Heroicon::OutlinedListBullet)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título'),
                    Textarea::make('intro')->label('Introducción')->rows(2),
                    Repeater::make('items')
                        ->label('Pasos (en orden)')
                        ->schema([
                            TextInput::make('title')->label('Título')->required(),
                            Textarea::make('text')->label('Texto')->rows(2),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->collapsible(),
                ]),
            Block::make('image_text')
                ->label('Imagen con texto')
                ->icon(Heroicon::OutlinedPhoto)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título')->required(),
                    Textarea::make('text')->label('Texto')->rows(4),
                    ...self::image('image', 'Imagen'),
                    Select::make('image_side')->label('Imagen a la')->options(['left' => 'Izquierda', 'right' => 'Derecha'])->default('right'),
                    ...self::buttons(withSecondary: false),
                ]),
            Block::make('featured_artworks')
                ->label('Obras destacadas')
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título'),
                    TextInput::make('limit')->label('Cantidad de obras')->numeric()->minValue(1)->maxValue(12)->default(6),
                    TextInput::make('button_label')->label('Texto del botón')->default('Ver catálogo'),
                ]),
            Block::make('team')
                ->label('Equipo')
                ->icon(Heroicon::OutlinedUserGroup)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título'),
                    Textarea::make('intro')->label('Introducción')->rows(2),
                    Repeater::make('members')
                        ->label('Integrantes')
                        ->schema([
                            TextInput::make('name')->label('Nombre')->required(),
                            Grid::make(2)->schema([
                                TextInput::make('role')->label('Cargo corto')->placeholder('CEO'),
                                TextInput::make('role_label')->label('Cargo completo'),
                            ]),
                            ...self::image('photo', 'Foto'),
                            Textarea::make('bio')->label('Reseña')->rows(3),
                            TagsInput::make('institutions')->label('Instituciones'),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['name'] ?? null),
                ]),
            Block::make('video')
                ->label('Video')
                ->icon(Heroicon::OutlinedPlayCircle)
                ->schema([
                    TextInput::make('eyebrow')->label('Antetítulo'),
                    TextInput::make('title')->label('Título'),
                    Textarea::make('text')->label('Texto')->rows(2),
                    TextInput::make('video_url')->label('Video (URL o ruta .mp4)')->required(),
                    ...self::image('poster', 'Imagen de portada del video'),
                ]),
            Block::make('faq')
                ->label('Preguntas frecuentes')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->schema([
                    TextInput::make('title')->label('Título')->default('Preguntas frecuentes'),
                    Repeater::make('items')
                        ->label('Preguntas')
                        ->schema([
                            TextInput::make('question')->label('Pregunta')->required(),
                            Textarea::make('answer')->label('Respuesta')->rows(3)->required(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['question'] ?? null),
                ]),
            Block::make('cta')
                ->label('Llamado a la acción')
                ->icon(Heroicon::OutlinedMegaphone)
                ->schema([
                    TextInput::make('title')->label('Título')->required(),
                    Textarea::make('text')->label('Texto')->rows(2),
                    ...self::buttons(),
                ]),
        ];
    }

    /**
     * Bloques listos para el frontend: las imágenes llegan como `<campo>_url` y
     * el bloque de obras destacadas trae sus obras publicadas.
     *
     * @param array<int, array{type: string, data: array}> $blocks
     */
    public static function forApi(?array $blocks): array
    {
        return collect($blocks ?? [])
            ->map(function (array $block) {
                $data = self::resolveImages($block['data'] ?? []);

                if ($block['type'] === 'featured_artworks' || ($block['type'] === 'hero' && ! empty($data['show_featured_artwork']))) {
                    $data['artworks'] = self::featuredArtworks($block['type'] === 'hero' ? 1 : (int) ($data['limit'] ?? 6));
                }

                return ['type' => $block['type'], 'data' => $data];
            })
            ->values()
            ->all();
    }

    private static function resolveImages(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::resolveImages($value);
            } elseif (in_array($key, self::IMAGE_KEYS, true)) {
                if (filled($value)) {
                    $data["{$key}_url"] = Disks::mediaUrl($value);
                }
                unset($data[$key]);
            }
        }

        return $data;
    }

    private static function featuredArtworks(int $limit): array
    {
        return Artwork::published()
            ->latest('published_at')
            ->limit(max(1, min(12, $limit)))
            ->get()
            ->map(fn (Artwork $a) => $a->only([
                'id', 'code', 'title', 'artist_name', 'image_url', 'technique', 'year', 'dimensions',
                'price', 'currency', 'show_price', 'availability', 'model_3d_url',
            ]))
            ->all();
    }

    /** @return array<\Filament\Schemas\Components\Component> */
    private static function image(string $field, string $label, ?string $help = null): array
    {
        return [
            Grid::make(2)->schema([
                FileUpload::make($field)
                    ->label($label)
                    ->helperText($help)
                    ->image()
                    ->disk(Disks::mediaName())
                    ->directory('sitio')
                    ->visibility('public')
                    ->maxSize(8192),
                TextInput::make("{$field}_url")
                    ->label("{$label} (URL o ruta, si no subes archivo)")
                    ->placeholder('/obras/madre-cusquena.jpg'),
            ]),
        ];
    }

    /** @return array<\Filament\Schemas\Components\Component> */
    private static function buttons(bool $withSecondary = true): array
    {
        $fields = [
            TextInput::make('primary_label')->label('Botón principal'),
            TextInput::make('primary_url')->label('Enlace del botón principal')->placeholder('/obras'),
        ];
        if ($withSecondary) {
            $fields[] = TextInput::make('secondary_label')->label('Botón secundario');
            $fields[] = TextInput::make('secondary_url')->label('Enlace del botón secundario');
        }

        return [Grid::make(2)->schema($fields)];
    }
}
