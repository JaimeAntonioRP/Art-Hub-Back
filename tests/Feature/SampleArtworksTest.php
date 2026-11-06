<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\SampleArtworksSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Obras de muestra de dominio público: se exhiben «No a la venta», con fuente y licencia. */
class SampleArtworksTest extends TestCase
{
    use RefreshDatabase;

    private function gioconda(): Artwork
    {
        return Artwork::where('title', 'La Gioconda')->sole();
    }

    public function test_the_thirteen_works_load_with_author_year_source_and_license(): void
    {
        $this->seed(SampleArtworksSeeder::class);

        $samples = Artwork::where('is_sample', true)->get();
        $this->assertCount(13, $samples);
        foreach ($samples as $artwork) {
            $this->assertSame(Artwork::STATUS_PUBLICADO, $artwork->status, $artwork->title);
            $this->assertSame('no_venta', $artwork->availability, $artwork->title);
            $this->assertFalse($artwork->show_price);
            $this->assertNull($artwork->price);
            $this->assertNotNull($artwork->artist, $artwork->title);
            $this->assertNotNull($artwork->year, $artwork->title);
            $this->assertSame('Wikimedia Commons', $artwork->source);
            $this->assertStringStartsWith('https://commons.wikimedia.org/wiki/File:', $artwork->source_url);
            $this->assertSame('Dominio público', $artwork->license);
            $this->assertStringStartsWith('/obras/muestras/', $artwork->image_url);
        }

        $leonardo = Artist::where('slug', 'leonardo-da-vinci')->sole();
        $this->assertSame([1452, 1519], [$leonardo->birth_year, $leonardo->death_year]);
        $this->assertNull($leonardo->bio); // sin biografías inventadas
        $this->assertTrue(Artwork::where('title', 'La gran ola de Kanagawa')->sole()->year_circa);

        // Se puede volver a ejecutar sin duplicar ni pisar lo editado.
        $this->gioconda()->update(['description' => 'Editada en el panel']);
        $this->seed(SampleArtworksSeeder::class);
        $this->assertSame(13, Artwork::where('is_sample', true)->count());
        $this->assertSame('Editada en el panel', $this->gioconda()->description);
    }

    public function test_the_public_page_shows_the_source_and_license(): void
    {
        $this->seed(SampleArtworksSeeder::class);
        $id = $this->gioconda()->id;

        $this->getJson("/api/artworks/{$id}")->assertOk()
            ->assertJsonPath('is_sample', true)
            ->assertJsonPath('artist.name', 'Leonardo da Vinci')
            ->assertJsonPath('year', 1503)
            ->assertJsonPath('source', 'Wikimedia Commons')
            ->assertJsonPath('license', 'Dominio público')
            ->assertJsonPath('availability', 'no_venta');

        $this->getJson('/api/artworks?availability=no_venta&per_page=48')
            ->assertJsonPath('meta.total', 13)
            ->assertJsonPath('data.0.is_sample', true);
    }

    public function test_samples_cannot_be_requested_sold_or_certified(): void
    {
        $this->seed(SampleArtworksSeeder::class);
        $artwork = $this->gioconda();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->postJson("/api/artworks/{$artwork->id}/requests", [
            'name' => 'Lucía', 'email' => 'lucia@example.com', 'type' => 'informacion', 'consent' => true,
        ])->assertStatus(422);

        $this->assertSame([], Sale::allowedTypesFor($artwork));
        try {
            Sale::register($artwork, ['type' => 'transferencia', 'date' => '2026-11-06', 'party_name' => 'X'], $admin);
            $this->fail('Una obra de muestra no se transfiere.');
        } catch (DomainException) {
        }

        $this->expectException(DomainException::class);
        Certificate::issueFor($artwork, $admin);
    }
}
