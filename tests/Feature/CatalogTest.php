<?php

namespace Tests\Feature;

use App\Filament\Resources\CommercialRequests\Pages\EditCommercialRequest;
use App\Filament\Resources\CommercialRequests\Pages\ListCommercialRequests;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\CommercialRequest;
use App\Models\User;
use App\Notifications\CommercialRequestReceived;
use App\Notifications\NewCommercialRequest;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** Semana 4: catálogo con filtros en el servidor (§12), ficha (§13) y solicitudes (§14). */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function artist(string $name, string $status = Artist::STATUS_APROBADO): Artist
    {
        return Artist::create(['name' => $name, 'slug' => str($name)->slug(), 'origin' => 'Cusco, Perú', 'status' => $status]);
    }

    private function published(Artist $artist, array $attrs = []): Artwork
    {
        return Artwork::create([
            'artist_id' => $artist->id,
            'title' => 'Obra',
            'status' => Artwork::STATUS_PUBLICADO,
            ...$attrs,
        ]);
    }

    private function requestPayload(array $overrides = []): array
    {
        return [
            'name' => 'Lucía Quispe',
            'email' => 'lucia@example.com',
            'phone' => '+51 999 000 111',
            'type' => 'intencion_compra',
            'message' => '¿Se puede enviar a Lima?',
            'consent' => true,
            ...$overrides,
        ];
    }

    public function test_catalog_filters_sorts_and_paginates_on_the_server(): void
    {
        $ana = $this->artist('Ana Mamani');
        $luis = $this->artist('Luis Huamán');
        $this->published($ana, ['title' => 'Apu dorado', 'technique' => 'Óleo', 'price' => 3000]);
        $this->published($ana, ['title' => 'Niebla', 'technique' => 'Acuarela', 'price' => 800, 'availability' => 'vendida']);
        $this->published($luis, ['title' => 'Mercado', 'technique' => 'Óleo', 'price' => 5000]);
        $this->published($luis, ['title' => 'Sin precio', 'technique' => 'Óleo', 'price' => 9000, 'show_price' => false]);
        Artwork::create(['artist_id' => $luis->id, 'title' => 'Borrador oculto', 'technique' => 'Grabado']);

        $this->getJson('/api/artworks')->assertOk()
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('facets.techniques', ['Acuarela', 'Óleo'])
            ->assertJsonPath('facets.artists.0.slug', 'ana-mamani')
            ->assertJsonPath('facets.artists.0.total', 2)
            ->assertJsonPath('facets.price.min', 800)
            ->assertJsonPath('facets.price.max', 5000); // la obra con «Consultar precio» no revela su precio

        $this->getJson('/api/artworks?artist=ana-mamani')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/artworks?technique=Óleo&availability=disponible')->assertJsonPath('meta.total', 3);
        $this->getJson('/api/artworks?q=merc')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Mercado');
        $this->getJson('/api/artworks?q=huam')->assertJsonPath('meta.total', 2); // también busca por artista

        // Un filtro de precio deja fuera las obras sin precio visible.
        $this->getJson('/api/artworks?price_min=1000&price_max=6000&sort=price_asc')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.title', 'Apu dorado')
            ->assertJsonPath('data.1.title', 'Mercado');

        $this->getJson('/api/artworks?sort=title&per_page=2&page=2')
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('data.0.title', 'Niebla')
            ->assertJsonPath('data.1.title', 'Sin precio');

        $this->getJson('/api/artworks?sort=caro&availability=regalada')->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'availability']);
    }

    public function test_artwork_page_includes_gallery_artist_and_related_works(): void
    {
        $ana = $this->artist('Ana Mamani');
        $artwork = $this->published($ana, ['title' => 'Apu dorado', 'support' => 'Lienzo', 'height_cm' => 80, 'width_cm' => 60, 'image_url' => '/obras/apu.jpg']);
        $artwork->images()->create(['kind' => 'detalle', 'path' => 'obras/apu-detalle.jpg', 'disk' => 'public']);
        $this->published($ana, ['title' => 'Niebla']);
        $this->published($this->artist('Luis Huamán'), ['title' => 'Ajena']);

        $this->getJson("/api/artworks/{$artwork->id}")->assertOk()
            ->assertJsonPath('code', $artwork->code)
            ->assertJsonPath('support', 'Lienzo')
            ->assertJsonPath('dimensions', '80 × 60 cm')
            ->assertJsonCount(2, 'images')
            ->assertJsonPath('images.0.is_primary', true)
            ->assertJsonPath('artist.slug', 'ana-mamani')
            ->assertJsonPath('artist.has_page', true)
            ->assertJsonCount(1, 'related')
            ->assertJsonPath('related.0.title', 'Niebla');
    }

    public function test_visitor_sends_a_request_and_team_and_visitor_are_notified(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);
        $artwork = $this->published($this->artist('Ana Mamani'));

        $this->postJson("/api/artworks/{$artwork->id}/requests", $this->requestPayload())
            ->assertCreated()
            ->assertJsonPath('message', 'Recibimos tu solicitud. Te escribiremos pronto.');

        $request = CommercialRequest::sole();
        $this->assertSame('nueva', $request->status);
        $this->assertSame('intencion_compra', $request->type);
        $this->assertSame($artwork->id, $request->artwork_id);
        $this->assertNull($request->user_id);

        Notification::assertSentTo($admin, NewCommercialRequest::class);
        Notification::assertCount(2); // el admin inactivo no recibe nada
        Notification::assertSentTo(new AnonymousNotifiable, CommercialRequestReceived::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'lucia@example.com');
    }

    public function test_request_requires_consent_rejects_bots_and_unpublished_works(): void
    {
        Notification::fake();
        $artist = $this->artist('Ana Mamani');
        $artwork = $this->published($artist);
        $draft = Artwork::create(['artist_id' => $artist->id, 'title' => 'Borrador']);

        $this->postJson("/api/artworks/{$artwork->id}/requests", $this->requestPayload(['consent' => false, 'email' => 'no-es-correo', 'type' => 'oferta']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consent', 'email', 'type']);
        $this->postJson("/api/artworks/{$artwork->id}/requests", $this->requestPayload(['website' => 'http://spam.example']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('website');
        $this->postJson("/api/artworks/{$draft->id}/requests", $this->requestPayload())->assertNotFound();

        $this->assertSame(0, CommercialRequest::count());
        Notification::assertNothingSent();
    }

    public function test_requests_are_rate_limited_per_ip(): void
    {
        Notification::fake();
        $artwork = $this->published($this->artist('Ana Mamani'));

        foreach (range(1, 3) as $_) {
            $this->postJson("/api/artworks/{$artwork->id}/requests", $this->requestPayload())->assertCreated();
        }
        $this->postJson("/api/artworks/{$artwork->id}/requests", $this->requestPayload())->assertTooManyRequests();
        $this->assertSame(3, CommercialRequest::count());
    }

    public function test_admin_manages_requests_in_the_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Filament::setCurrentPanel('admin');
        $artwork = $this->published($this->artist('Ana Mamani'), ['title' => 'Apu dorado']);
        $new = $artwork->commercialRequests()->create($this->requestPayload());
        $done = $artwork->commercialRequests()->create([...$this->requestPayload(['name' => 'Pedro']), 'status' => 'confirmada']);

        $this->actingAs($admin)->get('/admin/solicitudes')->assertOk()->assertSee('Apu dorado');
        $this->actingAs($admin)->get("/admin/solicitudes/{$new->id}/edit")->assertOk()->assertSee('lucia@example.com');
        $this->actingAs($admin)->get('/admin/solicitudes/create')->assertNotFound();

        Livewire::actingAs($admin)->test(ListCommercialRequests::class)
            ->filterTable('status', 'nueva')
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$done]);

        Livewire::actingAs($admin)->test(EditCommercialRequest::class, ['record' => $new->id])
            ->fillForm(['status' => 'contactado', 'admin_notes' => 'Llamé, pide envío a Lima.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('contactado', $new->fresh()->status);
        $this->assertSame('Llamé, pide envío a Lima.', $new->fresh()->admin_notes);
        $this->assertSame('Lucía Quispe', $new->fresh()->name); // los datos del interesado no se tocan

        $buyer = User::factory()->create(['role' => User::ROLE_COMPRADOR]);
        $this->actingAs($buyer)->get('/admin/solicitudes')->assertForbidden();
    }
}
