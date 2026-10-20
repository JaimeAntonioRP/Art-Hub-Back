<?php

namespace App\Filament\Artista\Pages;

use App\Filament\Schemas\ArtistProfileFields;
use App\Models\Artist;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** El artista completa su perfil y lo envía a revisión (§6). */
class MiPerfil extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $title = 'Mi perfil de artista';

    protected static ?string $navigationLabel = 'Mi perfil';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'mi-perfil';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->artist()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(ArtistProfileFields::sections())
            ->model($this->artist())
            ->operation('edit')
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->statusCallout(),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Guardar cambios')->submit('save'),
                        $this->submitForReviewAction(),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $this->artist()->update($this->form->getState());

        Notification::make()->title('Perfil guardado')->success()->send();
    }

    public function submitForReviewAction(): Action
    {
        return Action::make('submitForReview')
            ->label('Enviar a revisión')
            ->color('gray')
            ->visible(fn () => in_array($this->artist()->status, [Artist::STATUS_OBSERVADO, Artist::STATUS_RECHAZADO], true))
            ->requiresConfirmation()
            ->modalDescription('Guardaremos tus cambios y el equipo de ArtHub revisará tu perfil otra vez.')
            ->action(function () {
                $this->save();
                $this->artist()->transitionTo(Artist::STATUS_PENDIENTE);

                Notification::make()->title('Perfil enviado a revisión')->success()->send();
            });
    }

    private function statusCallout(): Callout
    {
        return Callout::make(fn () => 'Estado: '.Artist::STATUS_LABELS[$this->artist()->status])
            ->description(fn () => $this->statusDescription())
            ->status(fn () => match ($this->artist()->status) {
                Artist::STATUS_APROBADO => 'success',
                Artist::STATUS_OBSERVADO => 'warning',
                Artist::STATUS_RECHAZADO, Artist::STATUS_INACTIVO => 'danger',
                default => 'info',
            });
    }

    private function statusDescription(): string
    {
        $artist = $this->artist();
        $note = $artist->reviewNotes()->whereNull('resolved_at')->latest('id')->value('body');

        return match ($artist->status) {
            Artist::STATUS_PENDIENTE => 'El equipo de ArtHub está revisando tu perfil. Puedes seguir editándolo.',
            Artist::STATUS_APROBADO => 'Tu perfil está aprobado y visible en el sitio.',
            Artist::STATUS_INACTIVO => 'Tu perfil está desactivado. Escríbenos si quieres reactivarlo.',
            default => 'Observación del equipo: '.($note ?? 'revisa tu perfil.').' Corrige y pulsa «Enviar a revisión».',
        };
    }

    /** El perfil del artista que inició sesión (se crea si su cuenta aún no lo tiene). */
    private function artist(): Artist
    {
        $user = auth()->user();

        if (! $user->artist) {
            $user->setRelation('artist', $user->artist()->create([
                'name' => $user->name,
                'slug' => Artist::uniqueSlug($user->name),
                'status' => Artist::STATUS_PENDIENTE,
            ]));
        }

        return $user->artist;
    }
}
