<?php

namespace Tests\Feature;

use App\Filament\Artista\Resources\Artworks\Pages\EditArtwork as ArtistEditArtwork;
use App\Filament\Artista\Resources\Artworks\Pages\ViewArtwork;
use App\Filament\RelationManagers\ArtworkImagesRelationManager;
use App\Filament\Resources\Artworks\Pages\EditArtwork as AdminEditArtwork;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use App\Notifications\ArtworkReviewed;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/** Flujo de revisión y publicación de obras (§10), historial (§11) y escritorios (§15, §16). */
class ArtworkReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $artistUser;

    private Artist $artist;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->artistUser = User::factory()->create(['role' => User::ROLE_ARTISTA, 'name' => 'Rosa Huamán']);
        $this->artist = $this->artistUser->artist()->create(['name' => 'Rosa Huamán', 'slug' => 'rosa', 'origin' => 'Cusco', 'status' => 'aprobado']);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Obra lista para enviar: imagen, medidas y técnica. */
    private function completeArtwork(array $attrs = []): Artwork
    {
        $artwork = Artwork::create([
            'artist_id' => $this->artist->id, 'title' => 'Apus', 'technique' => 'Óleo',
            'height_cm' => 80, 'width_cm' => 120, 'price' => 3000, ...$attrs,
        ]);
        $artwork->images()->create(['path' => '/obras/apus.jpg']);

        return $artwork->fresh();
    }

    private function asArtist(): void
    {
        Filament::setCurrentPanel('artista');
        $this->actingAs($this->artistUser);
    }

    private function asAdmin(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->admin);
    }

    public function test_artist_sends_a_complete_draft_and_can_no_longer_edit_it(): void
    {
        $artwork = $this->completeArtwork();
        $this->asArtist();

        Livewire::test(ArtistEditArtwork::class, ['record' => $artwork->getKey()])
            ->callAction('submitForReview')
            ->assertNotified('Obra enviada a revisión')
            ->assertRedirect("/artista/obras/{$artwork->id}");

        $this->assertSame('en_revision', $artwork->fresh()->status);
        $this->assertFalse($this->artistUser->can('update', $artwork->fresh()));
        $this->get("/artista/obras/{$artwork->id}/edit")->assertForbidden();
        $this->get("/artista/obras/{$artwork->id}")->assertOk();
        // Al artista no le llega correo de su propio envío.
        Notification::assertNothingSent();
    }

    public function test_an_incomplete_artwork_cannot_be_sent(): void
    {
        $artwork = Artwork::create(['artist_id' => $this->artist->id, 'title' => 'Sin datos']);
        $this->asArtist();

        Livewire::test(ArtistEditArtwork::class, ['record' => $artwork->getKey()])
            ->callAction('submitForReview')
            ->assertNotified('No se pudo completar');

        $this->assertSame('borrador', $artwork->fresh()->status);
        $this->assertSame(['una imagen', 'el alto y el ancho', 'la técnica'], $artwork->missingForReview());
    }

    public function test_admin_observes_and_the_artist_fixes_and_resends(): void
    {
        $artwork = $this->completeArtwork(['status' => 'en_revision']);
        $this->asAdmin();

        Livewire::test(AdminEditArtwork::class, ['record' => $artwork->getKey()])
            ->callAction('observe', data: ['note' => ''])
            ->assertHasActionErrors(['note' => 'required']);
        $this->assertSame('en_revision', $artwork->fresh()->status);

        Livewire::test(AdminEditArtwork::class, ['record' => $artwork->getKey()])
            ->callAction('observe', data: ['note' => 'Sube una foto de la firma.'])
            ->assertHasNoActionErrors();

        $artwork->refresh();
        $this->assertSame('observado', $artwork->status);
        $this->assertSame('Sube una foto de la firma.', $artwork->reviewNotes()->value('body'));
        $this->assertSame(['de' => 'en_revision', 'a' => 'observado', 'nota' => 'Sube una foto de la firma.'], $artwork->history()->get()->last()->payload);
        Notification::assertSentTo($this->artistUser, ArtworkReviewed::class, fn (ArtworkReviewed $n) => $n->note === 'Sube una foto de la firma.');

        // Observada vuelve a ser editable por el artista, que corrige y reenvía.
        $this->assertTrue($this->artistUser->can('update', $artwork));
        $artwork->transitionTo(Artwork::STATUS_EN_REVISION, $this->artistUser);
        $this->assertSame('en_revision', $artwork->fresh()->status);
        $this->assertNotNull($artwork->reviewNotes()->value('resolved_at'));
    }

    public function test_approve_publish_and_unpublish_control_the_public_catalog(): void
    {
        $artwork = $this->completeArtwork(['status' => 'en_revision']);
        $this->asAdmin();

        $page = Livewire::test(AdminEditArtwork::class, ['record' => $artwork->getKey()])
            ->assertActionVisible('publish') // también puede publicar directamente
            ->assertActionHidden('unpublish')
            ->callAction('approve');
        $this->assertSame('aprobado', $artwork->fresh()->status);
        $this->getJson('/api/artworks')->assertJsonCount(0, 'data');

        $page->callAction('publish');
        $this->assertSame('publicado', $artwork->fresh()->status);
        $this->assertNotNull($artwork->fresh()->published_at);
        $this->getJson('/api/artworks')->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', $artwork->code);

        $page->callAction('unpublish');
        $this->getJson('/api/artworks')->assertJsonCount(0, 'data');
        // El admin puede previsualizarla; un visitante ya no.
        $this->getJson("/api/artworks/{$artwork->id}")->assertOk();
        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/artworks/{$artwork->id}")->assertNotFound();

        $this->assertSame(
            ['creada', 'estado_cambiado', 'estado_cambiado', 'estado_cambiado'], // aprobar, publicar, despublicar
            $artwork->history()->pluck('type')->all(),
        );
        Notification::assertSentToTimes($this->artistUser, ArtworkReviewed::class, 3);
    }

    public function test_rejected_artworks_are_locked_until_the_admin_reopens_them(): void
    {
        $artwork = $this->completeArtwork(['status' => 'en_revision']);
        $artwork->transitionTo(Artwork::STATUS_RECHAZADO, $this->admin, 'No corresponde al catálogo.');

        $this->assertFalse($this->artistUser->can('update', $artwork->fresh()));
        $this->assertFalse($artwork->fresh()->canTransitionTo(Artwork::STATUS_EN_REVISION, $this->artistUser));

        $artwork->fresh()->transitionTo(Artwork::STATUS_BORRADOR, $this->admin);
        $this->assertTrue($this->artistUser->can('update', $artwork->fresh()));
    }

    public function test_artist_cannot_approve_or_publish(): void
    {
        $artwork = $this->completeArtwork(['status' => 'en_revision']);

        $this->assertFalse($artwork->canTransitionTo(Artwork::STATUS_PUBLICADO, $this->artistUser));
        $this->expectException(InvalidArgumentException::class);
        $artwork->transitionTo(Artwork::STATUS_PUBLICADO, $this->artistUser);
    }

    public function test_admin_publishes_works_of_artists_without_account_directly(): void
    {
        $master = Artist::create(['name' => 'Maestro histórico', 'slug' => 'maestro', 'status' => 'aprobado']);
        $artwork = Artwork::create(['artist_id' => $master->id, 'title' => 'Virgen del Cusco']);

        $artwork->transitionTo(Artwork::STATUS_PUBLICADO, $this->admin);

        $this->assertSame('publicado', $artwork->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_gallery_is_read_only_for_the_artist_while_in_review(): void
    {
        $artwork = $this->completeArtwork(['status' => 'en_revision']);
        $this->asArtist();

        Livewire::test(ArtworkImagesRelationManager::class, ['ownerRecord' => $artwork, 'pageClass' => ViewArtwork::class])
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_history_is_visible_on_the_artwork(): void
    {
        $artwork = $this->completeArtwork(['status' => 'en_revision']);
        $artwork->transitionTo(Artwork::STATUS_OBSERVADO, $this->admin, 'Falta la firma.');
        $this->asAdmin();

        $this->get("/admin/obras/{$artwork->id}/edit")->assertOk()->assertSee('Historial')->assertSee('Observaciones');
    }

    public function test_dashboards_show_the_review_queue_and_the_artist_counters(): void
    {
        $this->completeArtwork(['status' => 'en_revision', 'title' => 'Por revisar']);
        $observed = $this->completeArtwork(['status' => 'en_revision', 'title' => 'Con nota']);
        $observed->transitionTo(Artwork::STATUS_OBSERVADO, $this->admin, 'Revisa las medidas.');

        $this->asAdmin();
        $this->get('/admin')->assertOk()->assertSee('Obras por revisar')->assertSee('Por revisar');

        $this->asArtist();
        $this->get('/artista')->assertOk()->assertSee('Con observaciones')->assertSee('Revisa las medidas.');
    }
}
