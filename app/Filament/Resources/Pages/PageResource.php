<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Schemas\PageBlocks;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Páginas públicas del sitio armadas con bloques (§4, contenidos públicos). */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Sitio web';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'paginas';

    protected static ?string $modelLabel = 'página';

    /** En español solo va mayúscula inicial: «Crear página». */
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Páginas';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Página')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Dirección')
                        ->prefix('/')
                        ->helperText('«inicio» es la portada del sitio.')
                        ->required()
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label('Descripción para buscadores')
                        ->maxLength(300)
                        ->rows(2),
                    Toggle::make('is_published')
                        ->label('Publicada')
                        ->default(true)
                        ->inline(false),
                ]),
            Builder::make('blocks')
                ->label('Contenido')
                ->blocks(PageBlocks::blocks())
                ->addActionLabel('Agregar bloque')
                ->collapsible()
                ->cloneable()
                ->blockNumbers(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->label('Página')->searchable()->sortable(),
                TextColumn::make('slug')->label('Dirección')->prefix('/'),
                TextColumn::make('blocks')
                    ->label('Bloques')
                    ->formatStateUsing(fn ($state, Page $record) => count($record->blocks ?? [])),
                IconColumn::make('is_published')->label('Publicada')->boolean(),
                TextColumn::make('updated_at')->label('Actualizada')->since()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')
                    ->label('Ver en el sitio')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Page $record) => self::publicUrl($record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function publicUrl(Page $page): string
    {
        return config('app.frontend_url').($page->slug === 'inicio' ? '/' : '/'.$page->slug);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
