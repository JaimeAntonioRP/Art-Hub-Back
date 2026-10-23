<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Artwork;
use App\Support\ArPlane;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Semana 8: «Ver en mi pared» (§27). */
class ArTest extends TestCase
{
    use RefreshDatabase;

    private Artwork $artwork;

    protected function setUp(): void
    {
        parent::setUp();

        config(['arthub.disks.media' => 'media-test']);
        Storage::fake('media-test');
        Http::preventStrayRequests();

        $artist = Artist::create(['name' => 'Rosa Huamán', 'slug' => 'rosa', 'origin' => 'Cusco, Perú', 'status' => Artist::STATUS_APROBADO]);
        $this->artwork = Artwork::create([
            'artist_id' => $artist->id, 'title' => 'Apus', 'status' => Artwork::STATUS_PUBLICADO,
            'height_cm' => 120, 'width_cm' => 90,
        ]);
        Storage::disk('media-test')->put('obras/apus.jpg', $this->jpeg(300, 400)); // vertical, 3:4
        $this->artwork->images()->create(['path' => 'obras/apus.jpg']);
    }

    private function jpeg(int $w, int $h): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    }

    /** @return array{array<string, mixed>, string} JSON y binario del GLB */
    private function parseGlb(string $glb): array
    {
        $this->assertSame('glTF', substr($glb, 0, 4));
        ['version' => $version, 'length' => $length] = unpack('Vversion/Vlength', substr($glb, 4, 8));
        $this->assertSame(2, $version);
        $this->assertSame(strlen($glb), $length);

        ['jsonLength' => $jsonLength] = unpack('VjsonLength', substr($glb, 12, 4));
        $this->assertSame('JSON', substr($glb, 16, 4));
        $json = json_decode(substr($glb, 20, $jsonLength), true);
        $this->assertSame("BIN\0", substr($glb, 24 + $jsonLength, 4));

        return [$json, substr($glb, 28 + $jsonLength)];
    }

    public function test_the_model_is_a_glb_at_real_scale_with_the_photo(): void
    {
        $response = $this->get("/api/artworks/{$this->artwork->id}/ar.glb")
            ->assertOk()
            ->assertHeader('content-type', 'model/gltf-binary');

        [$json, $bin] = $this->parseGlb($response->getContent());
        $this->assertSame('2.0', $json['asset']['version']);
        $this->assertCount(2, $json['meshes'][0]['primitives']); // frente con la foto + canto

        $front = $json['accessors'][$json['meshes'][0]['primitives'][0]['attributes']['POSITION']];
        $this->assertEqualsWithDelta([0.45, 1.2, 0], $front['max'], 0.0001); // 90 × 120 cm, centrado en x
        $this->assertEqualsWithDelta([-0.45, 0, 0], $front['min'], 0.0001);

        $image = $json['bufferViews'][$json['images'][0]['bufferView']];
        $this->assertSame('image/jpeg', $json['images'][0]['mimeType']);
        $this->assertSame("\xFF\xD8", substr($bin, $image['byteOffset'], 2)); // JPEG dentro del GLB
    }

    public function test_the_photo_sets_the_proportion_when_measures_are_swapped(): void
    {
        // Foto vertical 3:4, medidas cargadas como 90 de alto × 120 de ancho (al revés).
        $this->assertEqualsWithDelta([90, 120], ArPlane::realSize(120, 90, 4 / 3), 0.0001);
        // Foto apaisada 4:3 con las mismas medidas.
        $this->assertEqualsWithDelta([120, 90], ArPlane::realSize(120, 90, 3 / 4), 0.0001);
    }

    public function test_the_model_is_cached_and_rebuilt_when_the_artwork_changes(): void
    {
        $this->get("/api/artworks/{$this->artwork->id}/ar.glb")->assertOk();
        $first = Storage::disk('media-test')->files('ar');
        $this->assertCount(1, $first);

        $this->artwork->update(['width_cm' => 100]);
        $this->get("/api/artworks/{$this->artwork->id}/ar.glb")->assertOk();
        $second = Storage::disk('media-test')->files('ar');
        $this->assertCount(1, $second); // la versión anterior se borra
        $this->assertNotSame($first, $second);

        $this->getJson("/api/artworks/{$this->artwork->id}")
            ->assertJsonPath('ar_model_url', url("/api/artworks/{$this->artwork->id}/ar.glb?v=".ArPlane::cacheKey($this->artwork->fresh())));
    }

    public function test_no_model_without_photo_or_measures_or_publication(): void
    {
        $this->artwork->update(['height_cm' => null]);
        $this->get("/api/artworks/{$this->artwork->id}/ar.glb")->assertNotFound();
        $this->getJson("/api/artworks/{$this->artwork->id}")->assertJsonPath('ar_model_url', null);

        $this->artwork->update(['height_cm' => 120, 'status' => Artwork::STATUS_DESPUBLICADO]);
        $this->get("/api/artworks/{$this->artwork->id}/ar.glb")->assertNotFound();
        $this->get("/api/artworks/{$this->artwork->id}/ar-qr.png")->assertNotFound();
    }

    public function test_legacy_photos_are_read_from_the_site(): void
    {
        config(['app.frontend_url' => 'https://arthub.test']);
        $this->artwork->images()->delete();
        $this->artwork->images()->create(['path' => '/obras/antigua.jpg']);
        Http::fake(['https://arthub.test/obras/antigua.jpg' => Http::response($this->jpeg(400, 300))]);

        $this->get("/api/artworks/{$this->artwork->id}/ar.glb")->assertOk();
        Http::assertSent(fn ($request) => $request->url() === 'https://arthub.test/obras/antigua.jpg');
    }

    public function test_qr_opens_the_artwork_on_the_phone(): void
    {
        $png = $this->get("/api/artworks/{$this->artwork->id}/ar-qr.png")
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->getContent();

        $this->assertSame("\x89PNG", substr($png, 0, 4));
    }
}
