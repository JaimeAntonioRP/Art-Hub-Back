<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Page;
use App\Models\User;
use App\Support\SiteContent;
use Database\Seeders\SiteContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['arthub.disks.media' => 'media-test']);
        Storage::fake('media-test');
    }

    public function test_site_endpoint_returns_seeded_header_footer_and_contact(): void
    {
        $this->seed(SiteContentSeeder::class);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('brand.name', 'ArtHub')
            ->assertJsonPath('brand.logo_url', '/logifinal.png')
            ->assertJsonPath('header_menu.0', ['label' => 'Catálogo', 'url' => '/obras'])
            ->assertJsonPath('footer.columns.0.title', 'Navegación')
            ->assertJsonPath('contact.email', 'info@arthub.pe');
    }

    public function test_seeder_does_not_overwrite_edited_content(): void
    {
        $this->seed(SiteContentSeeder::class);
        SiteContent::save(['brand_name' => 'ArtHub Cusco', 'header_menu' => [['label' => 'Obras', 'url' => '/obras']]]);
        Page::where('slug', 'inicio')->update(['title' => 'Portada editada']);

        $this->seed(SiteContentSeeder::class);

        $this->assertSame('ArtHub Cusco', SiteContent::all()['brand_name']);
        $this->assertSame([['label' => 'Obras', 'url' => '/obras']], SiteContent::all()['header_menu']);
        $this->assertSame('Portada editada', Page::where('slug', 'inicio')->value('title'));
    }

    public function test_seeded_pages_avoid_excluded_claims(): void
    {
        $this->seed(SiteContentSeeder::class);

        foreach (['inicio', 'como-funciona', 'tecnologia'] as $slug) {
            $json = json_encode(Page::where('slug', $slug)->value('blocks'), JSON_UNESCAPED_UNICODE);
            $this->assertDoesNotMatchRegularExpression('/blockchain|invers|tokeniz|biom[eé]tric|fracci/i', $json, $slug);
        }
    }

    public function test_page_endpoint_resolves_images_and_featured_artworks(): void
    {
        $artist = Artist::create(['name' => 'Rosa', 'slug' => 'rosa', 'status' => 'aprobado']);
        $published = Artwork::create(['artist_id' => $artist->id, 'title' => 'Publicada', 'status' => 'publicado', 'image_url' => '/obras/p.jpg']);
        Artwork::create(['artist_id' => $artist->id, 'title' => 'Borrador']);
        Page::create(['slug' => 'inicio', 'title' => 'Inicio', 'blocks' => [
            ['type' => 'hero', 'data' => ['title' => 'Hola', 'image' => 'sitio/portada.jpg', 'image_url' => '/vieja.jpg']],
            ['type' => 'featured_artworks', 'data' => ['title' => 'Obras', 'limit' => 6]],
            ['type' => 'team', 'data' => ['members' => [['name' => 'Ana', 'photo_url' => '/equipo/ana.png']]]],
        ]]);

        $this->getJson('/api/pages/inicio')
            ->assertOk()
            ->assertJsonPath('blocks.0.data.image_url', Storage::disk('media-test')->url('sitio/portada.jpg'))
            ->assertJsonMissingPath('blocks.0.data.image')
            ->assertJsonCount(1, 'blocks.1.data.artworks')
            ->assertJsonPath('blocks.1.data.artworks.0.title', $published->title)
            ->assertJsonPath('blocks.2.data.members.0.photo_url', '/equipo/ana.png');
    }

    public function test_unpublished_pages_are_not_public(): void
    {
        Page::create(['slug' => 'borrador', 'title' => 'Borrador', 'is_published' => false]);

        $this->getJson('/api/pages/borrador')->assertNotFound();
        $this->getJson('/api/pages/no-existe')->assertNotFound();
    }

    public function test_admin_edits_site_settings_and_uploads_a_logo(): void
    {
        $this->seed(SiteContentSeeder::class);
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'brand_name' => 'ArtHub Perú',
                'logo' => UploadedFile::fake()->image('logo.png'),
                'social_links' => [['network' => 'instagram', 'url' => 'https://instagram.com/arthub']],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Ajustes guardados');

        $site = $this->getJson('/api/site')->json();
        $this->assertSame('ArtHub Perú', $site['brand']['name']);
        $this->assertStringContainsString('/sitio/', $site['brand']['logo_url']);
        $this->assertSame([['network' => 'instagram', 'url' => 'https://instagram.com/arthub']], $site['social_links']);
        // Lo que no se tocó sigue igual.
        $this->assertSame('info@arthub.pe', $site['contact']['email']);
    }

    public function test_admin_panel_lists_and_edits_pages(): void
    {
        $this->seed(SiteContentSeeder::class);
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get('/admin/paginas')->assertOk()->assertSee('Cómo funciona');
        $this->get('/admin/ajustes-del-sitio')->assertOk();

        $page = Page::firstWhere('slug', 'nosotros');
        Livewire::test(EditPage::class, ['record' => $page->getKey()])
            ->fillForm(['title' => 'Quiénes somos'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Quiénes somos', $page->fresh()->title);
    }
}
