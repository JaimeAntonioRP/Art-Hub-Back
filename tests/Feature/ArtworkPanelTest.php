<?php

namespace Tests\Feature;

use App\Filament\Artista\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Artista\Resources\Artworks\Pages\EditArtwork;
use App\Filament\Artista\Resources\Artworks\Pages\ListArtworks;
use App\Filament\RelationManagers\ArtworkDocumentsRelationManager;
use App\Filament\RelationManagers\ArtworkImagesRelationManager;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\ArtworkImage;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArtworkPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Artist $artist;

    protected function setUp(): void
    {
        parent::setUp();

        // Discos con otro nombre: si algo usara "public"/"local" fijo, estos tests fallarían.
        config(['arthub.disks.media' => 'media-test', 'arthub.disks.documents' => 'documents-test']);
        Storage::fake('media-test');
        Storage::fake('documents-test');
        Storage::disk('documents-test')->buildTemporaryUrlsUsing(
            fn (string $path) => "https://firmado.test/{$path}?expira=5min",
        );

        [$this->user, $this->artist] = $this->makeArtist('Rosa Huamán', 'rosa');
        Filament::setCurrentPanel('artista');
        $this->actingAs($this->user);
    }

    /** @return array{User, Artist} */
    private function makeArtist(string $name, string $slug): array
    {
        $user = User::factory()->create(['role' => User::ROLE_ARTISTA, 'name' => $name]);
        $artist = $user->artist()->create(['name' => $name, 'slug' => $slug, 'origin' => 'Cusco, Perú']);

        return [$user, $artist];
    }

    private function artwork(?Artist $artist = null, array $attrs = []): Artwork
    {
        return Artwork::create(['artist_id' => ($artist ?? $this->artist)->id, 'title' => 'Apus al amanecer', ...$attrs]);
    }

    public function test_artist_only_sees_and_edits_their_own_artworks(): void
    {
        $mine = $this->artwork();
        [, $other] = $this->makeArtist('Otro Artista', 'otro');
        $theirs = $this->artwork($other, ['title' => 'Ajena']);

        Livewire::test(ListArtworks::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        $this->get("/artista/obras/{$mine->id}/edit")->assertOk();
        $this->get("/artista/obras/{$theirs->id}/edit")->assertNotFound();
    }

    public function test_artist_registers_an_artwork_as_a_draft_of_their_own(): void
    {
        Livewire::test(CreateArtwork::class)
            ->fillForm([
                'title' => 'Hijas del Apu',
                'year' => 2022,
                'technique' => 'Óleo',
                'support' => 'Lienzo',
                'height_cm' => 95,
                'width_cm' => 120,
                'price' => 9200,
                'currency' => 'USD',
                'show_price' => true,
                'availability' => 'disponible',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $artwork = Artwork::firstWhere('title', 'Hijas del Apu');
        $this->assertSame($this->artist->id, $artwork->artist_id);
        $this->assertSame('borrador', $artwork->status);
        $this->assertSame('95 × 120 cm', $artwork->dimensions);
        $this->assertMatchesRegularExpression('/^AH-CUS-\d{4}-\d{6}$/', $artwork->code);
    }

    public function test_price_is_required_only_when_shown(): void
    {
        Livewire::test(CreateArtwork::class)
            ->fillForm(['title' => 'Sin precio', 'show_price' => true, 'price' => null, 'currency' => 'USD', 'availability' => 'disponible'])
            ->call('create')
            ->assertHasFormErrors(['price' => 'required']);

        Livewire::test(CreateArtwork::class)
            ->fillForm(['title' => 'Consultar', 'show_price' => false, 'price' => null, 'currency' => 'USD', 'availability' => 'disponible'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_artist_cannot_publish_or_reassign_from_the_form(): void
    {
        $artwork = $this->artwork(attrs: ['price' => 5000]);
        [, $other] = $this->makeArtist('Otro Artista', 'otro');

        Livewire::test(EditArtwork::class, ['record' => $artwork->getKey()])
            ->set('data.status', 'publicado')
            ->set('data.artist_id', $other->id)
            ->set('data.title', 'Título corregido')
            ->call('save')
            ->assertHasNoFormErrors();

        $artwork->refresh();
        $this->assertSame('Título corregido', $artwork->title);
        $this->assertSame('borrador', $artwork->status);
        $this->assertSame($this->artist->id, $artwork->artist_id);
    }

    public function test_artist_only_deletes_drafts(): void
    {
        $draft = $this->artwork();
        $published = $this->artwork(attrs: ['title' => 'Publicada', 'status' => 'publicado']);

        $this->assertTrue($this->user->can('delete', $draft));
        $this->assertFalse($this->user->can('delete', $published));
    }

    public function test_uploading_images_builds_the_gallery_on_the_media_disk(): void
    {
        $artwork = $this->artwork();

        Livewire::test(ArtworkImagesRelationManager::class, ['ownerRecord' => $artwork, 'pageClass' => EditArtwork::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'paths' => [UploadedFile::fake()->image('frente.jpg'), UploadedFile::fake()->image('firma.jpg')],
                'kind' => 'firma',
            ])
            ->assertHasNoActionErrors();

        $images = $artwork->images()->get();
        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_primary);
        $this->assertSame('principal', $images[0]->kind);
        $this->assertSame('firma', $images[1]->kind);
        $this->assertSame([1, 2], $images->pluck('position')->all());
        foreach ($images as $image) {
            Storage::disk('media-test')->assertExists($image->path);
            $this->assertStringStartsWith("obras/{$artwork->id}/", $image->path);
        }
        $this->assertSame(Storage::disk('media-test')->url($images[0]->path), $artwork->fresh()->image_url);
    }

    public function test_choosing_a_new_primary_image_and_deleting_the_primary(): void
    {
        $artwork = $this->artwork();
        Storage::disk('media-test')->put('obras/a.jpg', 'a');
        Storage::disk('media-test')->put('obras/b.jpg', 'b');
        $first = $artwork->images()->create(['path' => 'obras/a.jpg']);
        $second = $artwork->images()->create(['path' => 'obras/b.jpg', 'kind' => 'detalle']);

        Livewire::test(ArtworkImagesRelationManager::class, ['ownerRecord' => $artwork, 'pageClass' => EditArtwork::class])
            ->callAction(TestAction::make('makePrimary')->table($second));

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertSame('adicional', $first->fresh()->kind);
        $this->assertTrue($second->fresh()->is_primary);

        $second->fresh()->delete();

        Storage::disk('media-test')->assertMissing('obras/b.jpg');
        $this->assertTrue($first->fresh()->is_primary);
    }

    public function test_document_upload_stores_privately_with_its_sha256(): void
    {
        $artwork = $this->artwork();
        $content = "%PDF-1.4\nDeclaración de autoría de Rosa Huamán";

        Livewire::test(ArtworkDocumentsRelationManager::class, ['ownerRecord' => $artwork, 'pageClass' => EditArtwork::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'category' => 'declaracion',
                'title' => 'Declaración de autoría',
                'file' => UploadedFile::fake()->createWithContent('declaracion.pdf', $content),
            ])
            ->assertHasNoActionErrors();

        $document = $artwork->documents()->with('latestVersion')->first();
        $version = $document->latestVersion;
        $this->assertSame('privado', $document->visibility);
        $this->assertSame($this->user->id, $document->created_by);
        $this->assertSame(1, $version->version);
        $this->assertSame('documents-test', $version->disk);
        $this->assertSame('declaracion.pdf', $version->original_name);
        $this->assertSame(hash('sha256', $content), $version->sha256);
        $this->assertSame(strlen($content), $version->size);
        Storage::disk('documents-test')->assertExists($version->path);
        Storage::disk('media-test')->assertMissing($version->path);
    }

    public function test_documents_reject_unsupported_files(): void
    {
        $artwork = $this->artwork();

        Livewire::test(ArtworkDocumentsRelationManager::class, ['ownerRecord' => $artwork, 'pageClass' => EditArtwork::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'category' => 'otro',
                'title' => 'Programa',
                'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            ])
            ->assertHasActionErrors(['file']);

        $this->assertSame(0, Document::count());
    }

    public function test_document_download_requires_permission_and_keeps_the_original_name(): void
    {
        $artwork = $this->artwork();
        Storage::disk('documents-test')->put('expedientes/1/x8f2a.pdf', 'acta');
        $document = $artwork->documents()->create(['category' => 'informe', 'title' => 'Acta']);
        $version = $document->addVersion('expedientes/1/x8f2a.pdf', 'Acta de entrega.pdf', $this->user);
        $url = route('documents.download', $version);

        $this->get($url)->assertOk()->assertDownload('Acta de entrega.pdf');

        [$otherUser] = $this->makeArtist('Otro Artista', 'otro');
        $this->actingAs($otherUser)->get($url)->assertForbidden();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get($url)->assertOk();

        auth()->logout();
        $this->get($url)->assertForbidden();
    }

    public function test_on_r2_the_download_redirects_to_a_short_signed_link(): void
    {
        // El disco falso responde como R2: un enlace firmado en lugar de enviar el archivo.
        config(['filesystems.disks.documents-test.driver' => 's3']);
        $artwork = $this->artwork();
        Storage::disk('documents-test')->put('expedientes/1/acta.pdf', 'acta');
        $version = $artwork->documents()->create(['category' => 'informe', 'title' => 'Acta'])
            ->addVersion('expedientes/1/acta.pdf', 'acta.pdf', $this->user);

        $this->get(route('documents.download', $version))
            ->assertRedirect('https://firmado.test/expedientes/1/acta.pdf?expira=5min');
    }

    public function test_deleting_an_artwork_removes_its_files(): void
    {
        $artwork = $this->artwork();
        Storage::disk('media-test')->put('obras/x.jpg', 'x');
        Storage::disk('documents-test')->put('expedientes/x.pdf', 'x');
        $artwork->images()->create(['path' => 'obras/x.jpg']);
        $artwork->documents()->create(['category' => 'otro', 'title' => 'X'])->addVersion('expedientes/x.pdf', 'x.pdf');

        $artwork->delete();

        Storage::disk('media-test')->assertMissing('obras/x.jpg');
        Storage::disk('documents-test')->assertMissing('expedientes/x.pdf');
        $this->assertSame(0, ArtworkImage::count());
    }
}
