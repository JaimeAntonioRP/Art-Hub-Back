<?php

namespace App\Filament\Pages;

use App\Support\Disks;
use App\Support\SiteContent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Marca, encabezado, pie de página y contacto del sitio público. */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sitio web';

    protected static ?string $title = 'Ajustes del sitio';

    protected static ?string $slug = 'ajustes-del-sitio';

    protected static ?int $navigationSort = 1;

    /** Redes que el frontend sabe dibujar como ícono. */
    private const NETWORKS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
        'whatsapp' => 'WhatsApp',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteContent::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Marca')
                    ->columns(2)
                    ->schema([
                        TextInput::make('brand_name')->label('Nombre')->required()->maxLength(80),
                        TextInput::make('brand_tagline')->label('Lema')->maxLength(120),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->disk(Disks::mediaName())
                            ->directory('sitio')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('PNG o SVG con fondo transparente.'),
                        TextInput::make('logo_url')
                            ->label('Logo (URL o ruta, si no subes archivo)')
                            ->placeholder('/logifinal.png'),
                        Textarea::make('meta_description')
                            ->label('Descripción para buscadores')
                            ->maxLength(300)
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Encabezado')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('header_cta_label')->label('Botón destacado (texto)')->placeholder('Ver colección'),
                            TextInput::make('header_cta_url')->label('Botón destacado (dirección)')->placeholder('/obras'),
                        ]),
                        Repeater::make('header_menu')
                            ->label('Menú principal')
                            ->schema(self::linkFields())
                            ->columns(2)
                            ->reorderable()
                            ->addActionLabel('Agregar enlace')
                            ->itemLabel(fn (array $state) => $state['label'] ?? null),
                    ]),
                Section::make('Pie de página')
                    ->schema([
                        Textarea::make('footer_about')->label('Texto bajo el logo')->rows(2),
                        Repeater::make('footer_columns')
                            ->label('Columnas de enlaces')
                            ->schema([
                                TextInput::make('title')->label('Título de la columna')->required(),
                                Repeater::make('links')
                                    ->label('Enlaces')
                                    ->schema(self::linkFields())
                                    ->columns(2)
                                    ->addActionLabel('Agregar enlace'),
                            ])
                            ->collapsible()
                            ->addActionLabel('Agregar columna')
                            ->itemLabel(fn (array $state) => $state['title'] ?? null),
                        TextInput::make('copyright')->label('Texto de derechos')->placeholder('© 2026 ArtHub S.A.C.'),
                    ]),
                Section::make('Contacto y redes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('contact_email')->label('Correo')->email(),
                        TextInput::make('contact_phone')->label('Teléfono'),
                        TextInput::make('contact_address')->label('Dirección'),
                        Repeater::make('social_links')
                            ->label('Redes sociales')
                            ->schema([
                                Select::make('network')->label('Red')->options(self::NETWORKS)->required(),
                                TextInput::make('url')->label('Enlace')->url()->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Agregar red')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Guardar cambios')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        SiteContent::save($this->form->getState());

        Notification::make()
            ->title('Ajustes guardados')
            ->body('El sitio público los muestra en menos de un minuto.')
            ->success()
            ->send();
    }

    /** @return array<\Filament\Schemas\Components\Component> */
    private static function linkFields(): array
    {
        return [
            TextInput::make('label')->label('Texto')->required(),
            TextInput::make('url')->label('Dirección')->placeholder('/obras')->required(),
        ];
    }
}
