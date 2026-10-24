<?php

namespace Tests\Feature;

use App\Filament\Artista\Resources\Artworks\Pages\EditArtwork as ArtistEditArtwork;
use App\Filament\Artista\Resources\Artworks\Pages\ViewArtwork as ArtistViewArtwork;
use App\Filament\RelationManagers\ArtworkDocumentsRelationManager;
use App\Filament\Resources\Artworks\Pages\EditArtwork as AdminEditArtwork;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\CommercialRequest;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Constraints\SeeInOrder;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/** Semana 5: huellas (§21), integridad (§22), versiones (§23) e historial ampliado (§24). */
class FingerprintTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Artwork $artwork;

    protected function setUp(): void
    {
        parent::setUp();

        config(['arthub.disks.media' => 'media-test', 'arthub.disks.documents' => 'documents-test']);
        Storage::fake('media-test');
        Storage::fake('documents-test');

        $this->user = User::factory()->create(['role' => User::ROLE_ARTISTA, 'name' => 'Rosa Huamán']);
        $artist = $this->user->artist()->create(['name' => 'Rosa Huamán', 'slug' => 'rosa', 'origin' => 'Cusco, Perú', 'status' => Artist::STATUS_APROBADO]);
        $this->artwork = Artwork::create(['artist_id' => $artist->id, 'title' => 'Apus al amanecer']);
    }

    /** Documento con su primera versión ya guardada. */
    private function document(string $content = 'version uno'): Document
    {
        Storage::disk('documents-test')->put('expedientes/acta-v1.pdf', $content);
        $document = $this->artwork->documents()->create(['category' => 'procedencia', 'title' => 'Acta de procedencia']);
        $document->addVersion('expedientes/acta-v1.pdf', 'acta.pdf', $this->user);

        return $document;
    }

    private function asArtist(string $page = ArtistEditArtwork::class)
    {
        Filament::setCurrentPanel('artista');
        $this->actingAs($this->user);

        return Livewire::test(ArtworkDocumentsRelationManager::class, ['ownerRecord' => $this->artwork, 'pageClass' => $page]);
    }

    private function asAdmin()
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        return Livewire::test(ArtworkDocumentsRelationManager::class, ['ownerRecord' => $this->artwork, 'pageClass' => AdminEditArtwork::class]);
    }

    public function test_a_new_version_keeps_the_previous_one_and_is_recorded_in_the_history(): void
    {
        $document = $this->document();
        $new = "%PDF-1.4\nActa corregida";

        $this->asArtist()
            ->callAction(TestAction::make('newVersion')->table($document), data: [
                'file' => UploadedFile::fake()->createWithContent('acta-corregida.pdf', $new),
                'reason' => 'Se corrigió la fecha de adquisición.',
            ])
            ->assertHasNoActionErrors();

        $versions = $document->versions()->get();
        $this->assertSame([1, 2], $versions->pluck('version')->all());
        $this->assertSame(hash('sha256', 'version uno'), $versions[0]->sha256);
        $this->assertSame(hash('sha256', $new), $versions[1]->sha256);
        $this->assertSame('sha256', $versions[1]->hash_algorithm);
        $this->assertSame($this->user->id, $versions[1]->uploaded_by);
        $this->assertSame('Se corrigió la fecha de adquisición.', $versions[1]->reason);
        Storage::disk('documents-test')->assertExists($versions[0]->path); // la anterior no se sobrescribe
        $this->assertSame(2, $document->fresh()->latestVersion->version);

        $events = $this->artwork->history()->whereIn('type', ['documento_agregado', 'documento_versionado'])->get();
        $this->assertSame(['documento_agregado', 'documento_versionado'], $events->pluck('type')->all());
        $this->assertSame('Se corrigió la fecha de adquisición.', $events[1]->payload['motivo']);
        $this->assertSame(hash('sha256', $new), $events[1]->payload['huella']);
        $this->assertSame($this->user->id, $events[1]->actor_id);
    }

    public function test_a_new_version_requires_a_reason(): void
    {
        $document = $this->document();

        $this->asArtist()
            ->callAction(TestAction::make('newVersion')->table($document), data: [
                'file' => UploadedFile::fake()->createWithContent('otra.pdf', 'otra'),
                'reason' => '',
            ])
            ->assertHasActionErrors(['reason' => 'required']);
        $this->assertSame(1, $document->versions()->count());

        Storage::disk('documents-test')->put('expedientes/otra.pdf', 'otra');
        $this->expectException(InvalidArgumentException::class);
        $document->addVersion('expedientes/otra.pdf', 'otra.pdf', $this->user, '   ');
    }

    public function test_artist_cannot_add_versions_while_the_artwork_is_under_review(): void
    {
        $document = $this->document();
        $this->artwork->forceFill(['status' => Artwork::STATUS_EN_REVISION])->saveQuietly();

        $this->asArtist(ArtistViewArtwork::class)
            ->assertActionHidden(TestAction::make('newVersion')->table($document))
            ->assertActionVisible(TestAction::make('versions')->table($document))
            ->assertActionHidden(TestAction::make('verifyAll')->table());
    }

    public function test_versions_list_shows_every_version_with_its_fingerprint(): void
    {
        $document = $this->document();
        Storage::disk('documents-test')->put('expedientes/acta-v2.pdf', 'version dos');
        $document->addVersion('expedientes/acta-v2.pdf', 'acta-v2.pdf', $this->user, 'Firma legalizada');

        $this->asArtist()
            ->mountAction(TestAction::make('versions')->table($document))
            ->assertActionMounted(TestAction::make('versions')->table($document));

        // El modal dibuja esta vista; la más reciente va primero.
        $html = view('filament.document-versions', ['versions' => $document->versions()->with('uploader')->get()->sortByDesc('version')])->render();
        $this->assertThat(['Versión 2', 'Vigente', 'Firma legalizada', hash('sha256', 'version dos'), 'Versión 1', hash('sha256', 'version uno')], new SeeInOrder($html));
        $this->assertStringContainsString(route('documents.download', $document->versions[0]), $html);
    }

    public function test_admin_detects_an_altered_file(): void
    {
        $intact = $this->document();
        Storage::disk('documents-test')->put('expedientes/peritaje.pdf', 'peritaje original');
        $tampered = $this->artwork->documents()->create(['category' => 'peritaje', 'title' => 'Peritaje']);
        $tampered->addVersion('expedientes/peritaje.pdf', 'peritaje.pdf', $this->user);
        Storage::disk('documents-test')->put('expedientes/peritaje.pdf', 'peritaje cambiado a mano');

        $this->asAdmin()
            ->callAction(TestAction::make('verifyAll')->table())
            ->assertNotified('Integridad no verificada');

        $this->assertTrue($intact->latestVersion->fresh()->verification_ok);
        $this->assertNotNull($intact->latestVersion->fresh()->verified_at);
        $this->assertFalse($tampered->latestVersion->fresh()->verification_ok);

        $failure = $this->artwork->history()->where('type', 'integridad_fallida')->sole();
        $this->assertSame(['documento' => 'Peritaje', 'version' => 1, 'motivo' => 'la huella no coincide'], $failure->payload);
    }

    public function test_integrity_command_reports_missing_and_altered_files(): void
    {
        $document = $this->document();

        $this->artisan('arthub:comprobar-integridad')->assertSuccessful();
        $this->assertSame(0, $this->artwork->history()->where('type', 'integridad_fallida')->count());

        Storage::disk('documents-test')->delete('expedientes/acta-v1.pdf');

        $this->artisan('arthub:comprobar-integridad', ['--obra' => $this->artwork->id])->assertFailed();
        $this->assertFalse($document->latestVersion->fresh()->verification_ok);
        $this->assertSame('archivo no encontrado', $this->artwork->history()->where('type', 'integridad_fallida')->sole()->payload['motivo']);
    }

    public function test_integrity_check_runs_every_day(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'arthub:comprobar-integridad'));

        $this->assertNotNull($event, 'La comprobación de integridad no está programada.');
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertSame('America/Lima', $event->timezone);
    }

    public function test_deleting_a_document_is_recorded(): void
    {
        $document = $this->document();
        $this->actingAs($this->user);

        $document->delete();

        $event = $this->artwork->history()->where('type', 'documento_eliminado')->sole();
        $this->assertSame(['documento' => 'Acta de procedencia', 'versiones' => 1], $event->payload);
        $this->assertSame($this->user->id, $event->actor_id);
        Storage::disk('documents-test')->assertMissing('expedientes/acta-v1.pdf');
    }

    public function test_uploaded_images_keep_their_fingerprint(): void
    {
        Storage::disk('media-test')->put('obras/frente.jpg', 'pixeles');
        $uploaded = $this->artwork->images()->create(['path' => 'obras/frente.jpg']);
        $legacy = $this->artwork->images()->create(['path' => '/obras/antigua.jpg']);

        $this->assertSame(hash('sha256', 'pixeles'), $uploaded->sha256);
        $this->assertNull($legacy->sha256); // las rutas antiguas del sitio no están en el disco
    }

    public function test_requests_appear_in_the_history_without_personal_data(): void
    {
        $request = $this->artwork->commercialRequests()->create([
            'name' => 'Lucía Quispe', 'email' => 'lucia@example.com', 'type' => 'intencion_compra',
        ]);
        $request->update(['status' => 'contactado', 'admin_notes' => 'Llamar el lunes']);

        $events = $this->artwork->history()->whereIn('type', ['solicitud_recibida', 'solicitud_actualizada'])->get();
        $this->assertSame(['tipo' => 'intencion_compra'], $events[0]->payload);
        $this->assertSame(['tipo' => 'intencion_compra', 'de' => 'nueva', 'a' => 'contactado'], $events[1]->payload);
        $this->assertStringNotContainsString('lucia', json_encode($events->pluck('payload')));

        CommercialRequest::first()->update(['admin_notes' => 'Otra nota']); // sin cambio de estado, no hay evento
        $this->assertSame(2, $this->artwork->history()->where('type', 'like', 'solicitud%')->count());
    }
}
