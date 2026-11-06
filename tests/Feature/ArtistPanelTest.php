<?php

namespace Tests\Feature;

use App\Filament\Artista\Pages\MiPerfil;
use App\Models\Artist;
use App\Models\User;
use App\Notifications\ArtistProfileReviewed;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArtistPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_ARTISTA, 'name' => 'Ana Quispe']);
        Filament::setCurrentPanel('artista');
        $this->actingAs($this->user);
    }

    public function test_artist_panel_opens_on_their_dashboard(): void
    {
        $this->get('/artista')->assertOk()->assertSee('Obras registradas')->assertSee('Obras por corregir');
        $this->get('/artista/mi-perfil')->assertOk()->assertSee('Pendiente de revisión');
        $this->get('/admin')->assertForbidden();
    }

    public function test_artist_completes_and_saves_their_profile(): void
    {
        Livewire::test(MiPerfil::class)
            ->fillForm([
                'artistic_name' => 'Anaq',
                'bio' => 'Pintora cusqueña.',
                'techniques' => ['Óleo', 'Acuarela'],
                'social_links' => ['Instagram' => 'https://instagram.com/anaq'],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Perfil guardado');

        $artist = $this->user->artist()->first();
        $this->assertSame('Anaq', $artist->artistic_name);
        $this->assertSame(['Óleo', 'Acuarela'], $artist->techniques);
        $this->assertSame('pendiente', $artist->status);
    }

    public function test_artist_uploads_a_profile_photo_from_the_phone(): void
    {
        config(['arthub.disks.media' => 'media-test']);
        Storage::fake('media-test');

        // 3,5 MB: más que el límite de fábrica de PHP (2 MB), dentro del permitido (4 MB).
        Livewire::test(MiPerfil::class)
            ->fillForm(['profile_image_url' => UploadedFile::fake()->image('foto.jpg', 1600, 1600)->size(3500)])
            ->call('save')
            ->assertHasNoFormErrors();

        $artist = $this->user->artist()->first();
        $this->assertStringStartsWith('artists/', $artist->profile_image_url);
        Storage::disk('media-test')->assertExists($artist->profile_image_url);
        $this->assertSame(Storage::disk('media-test')->url($artist->profile_image_url), $artist->photo_url);

        Livewire::test(MiPerfil::class)
            ->fillForm(['profile_image_url' => UploadedFile::fake()->image('grande.jpg')->size(5000)])
            ->call('save')
            ->assertHasFormErrors(['profile_image_url']);
    }

    public function test_artist_resubmits_an_observed_profile(): void
    {
        Notification::fake();
        $artist = $this->user->artist()->create(['name' => 'Ana Quispe', 'slug' => 'ana', 'status' => 'pendiente']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $artist->transitionTo(Artist::STATUS_OBSERVADO, $admin, 'Falta tu biografía.');

        Livewire::test(MiPerfil::class)
            ->assertSee('Falta tu biografía.')
            ->assertActionVisible('submitForReview')
            ->fillForm(['bio' => 'Pintora cusqueña.'])
            ->callAction('submitForReview')
            ->assertNotified('Perfil enviado a revisión');

        $artist->refresh();
        $this->assertSame('pendiente', $artist->status);
        $this->assertSame('Pintora cusqueña.', $artist->bio);
        $this->assertNotNull($artist->reviewNotes()->value('resolved_at'));
        // Solo la observación del admin genera correo; el reenvío del artista no.
        Notification::assertSentToTimes($this->user, ArtistProfileReviewed::class, 1);
    }

    public function test_resubmit_is_hidden_while_pending(): void
    {
        Livewire::test(MiPerfil::class)->assertActionHidden('submitForReview');
    }
}
