<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtworkTest extends TestCase
{
    use RefreshDatabase;

    private function artist(array $attrs = []): Artist
    {
        return Artist::create([
            'name'   => 'Augusto Garcia Peñalva',
            'slug'   => 'augusto-garcia-penalva',
            'origin' => 'Cusco, Perú',
            'status' => Artist::STATUS_APROBADO,
            ...$attrs,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_new_artwork_gets_code_and_creation_event(): void
    {
        $artwork = Artwork::create(['artist_id' => $this->artist()->id, 'title' => 'Madre Cusqueña']);

        $this->assertSame(sprintf('AH-CUS-%d-%06d', now()->year, $artwork->id), $artwork->code);
        $this->assertSame('borrador', $artwork->fresh()->status);
        $this->assertSame(['creada'], $artwork->history()->pluck('type')->all());
    }

    public function test_legacy_fields_map_to_new_schema(): void
    {
        $this->artist();

        $artwork = Artwork::create([
            'title'       => 'Hijas del Apu',
            'artist_name' => 'Augusto Garcia Peñalva',
            'dimensions'  => '95 × 120 cm',
            'image_url'   => '/obras/hijas.jpg',
        ])->fresh();

        $this->assertSame('Augusto Garcia Peñalva', $artwork->artist_name);
        $this->assertSame('95.00', $artwork->height_cm);
        $this->assertSame('120.00', $artwork->width_cm);
        $this->assertSame('95 × 120 cm', $artwork->dimensions);
        $this->assertSame('/obras/hijas.jpg', $artwork->image_url);
        $this->assertTrue($artwork->images->first()->is_primary);

        $artwork->update(['image_url' => '/obras/hijas-v2.jpg']);
        $this->assertSame(1, $artwork->images()->count());
        $this->assertSame('/obras/hijas-v2.jpg', $artwork->fresh()->image_url);
    }

    public function test_status_and_availability_changes_are_recorded(): void
    {
        $artwork = Artwork::create(['artist_id' => $this->artist()->id, 'title' => 'Obra']);

        $artwork->update(['status' => 'publicado']);
        $artwork->update(['availability' => 'vendida', 'price' => 5000]);

        $this->assertNotNull($artwork->fresh()->published_at);
        $this->assertSame(
            ['creada', 'estado_cambiado', 'disponibilidad_cambiada', 'editada'],
            $artwork->history()->pluck('type')->all(),
        );
        $this->assertSame(['de' => 'borrador', 'a' => 'publicado'], $artwork->history()->skip(1)->first()->payload);
    }

    public function test_public_catalog_only_shows_published_artworks(): void
    {
        $artist = $this->artist();
        $published = Artwork::create(['artist_id' => $artist->id, 'title' => 'Publicada', 'status' => 'publicado']);
        $draft = Artwork::create(['artist_id' => $artist->id, 'title' => 'Borrador']);

        $this->getJson('/api/artworks')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $published->id);
        $this->getJson("/api/artworks/{$draft->id}")->assertNotFound();
        $this->getJson("/api/artists/{$artist->slug}")->assertOk()
            ->assertJsonPath('artist.total_obras', 1)
            ->assertJsonCount(1, 'artworks');
    }

    public function test_admin_creates_artwork_with_current_panel_payload(): void
    {
        $this->artist();

        $this->actingAs($this->admin())->postJson('/api/artworks', [
            'title'       => 'Ecos de la Montaña',
            'artist_name' => 'Augusto Garcia Peñalva',
            'price'       => 9600,
            'description' => 'Óleo',
            'image_url'   => '/obras/ecos.jpg',
        ])->assertCreated()
            ->assertJsonPath('status', 'publicado')
            ->assertJsonPath('availability', 'disponible')
            ->assertJsonPath('image_url', '/obras/ecos.jpg');
    }

    public function test_non_admin_cannot_manage_artworks(): void
    {
        $buyer = User::factory()->create(['role' => User::ROLE_COMPRADOR]);

        $this->actingAs($buyer)->getJson('/api/admin/artworks')->assertForbidden();
        $this->actingAs($buyer)->postJson('/api/artworks', ['title' => 'x'])->assertForbidden();
    }

    public function test_artist_with_artworks_cannot_be_deleted(): void
    {
        $artist = $this->artist();
        Artwork::create(['artist_id' => $artist->id, 'title' => 'Obra']);

        $this->actingAs($this->admin())->deleteJson("/api/artists/{$artist->id}")->assertStatus(409);
        $this->assertModelExists($artist);
    }

    public function test_simulated_purchase_and_ai_endpoints_are_gone(): void
    {
        $this->actingAs($this->admin());

        $this->postJson('/api/purchase')->assertNotFound();
        $this->postJson('/api/verify')->assertNotFound();
    }

    public function test_dimension_parsing(): void
    {
        $this->assertSame(['height_cm' => 30.0, 'width_cm' => 20.0, 'depth_cm' => 15.0], Artwork::parseDimensions('30x20x15 cm'));
        $this->assertSame(['height_cm' => 60.5, 'width_cm' => 90.0, 'depth_cm' => null], Artwork::parseDimensions('60,5 x 90 cm'));
        $this->assertSame(['height_cm' => null, 'width_cm' => null, 'depth_cm' => null], Artwork::parseDimensions('variable'));
    }
}
