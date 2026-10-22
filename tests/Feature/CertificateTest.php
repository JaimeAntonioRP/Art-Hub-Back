<?php

namespace Tests\Feature;

use App\Filament\RelationManagers\ArtworkCertificatesRelationManager;
use App\Filament\Artista\Resources\Artworks\Pages\ViewArtwork as ArtistViewArtwork;
use App\Filament\Resources\Artworks\Pages\EditArtwork as AdminEditArtwork;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\User;
use DomainException;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/** Semana 6: certificado PDF con QR (§19), estados y portal de verificación (§20), integridad (§22). */
class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $artistUser;

    private Artwork $artwork;

    protected function setUp(): void
    {
        parent::setUp();

        config(['arthub.disks.media' => 'media-test', 'arthub.disks.documents' => 'documents-test', 'app.frontend_url' => 'https://arthub.test']);
        Storage::fake('media-test');
        Storage::fake('documents-test');
        Http::preventStrayRequests();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->artistUser = User::factory()->create(['role' => User::ROLE_ARTISTA]);
        $artist = $this->artistUser->artist()->create(['name' => 'Rosa Huamán', 'slug' => 'rosa', 'origin' => 'Cusco, Perú', 'status' => Artist::STATUS_APROBADO]);

        $this->artwork = Artwork::create([
            'artist_id' => $artist->id, 'title' => 'Apus al amanecer', 'technique' => 'Óleo', 'support' => 'Lienzo',
            'height_cm' => 80, 'width_cm' => 60.5, 'year' => 2024, 'status' => Artwork::STATUS_PUBLICADO,
        ]);

        // Imagen principal subida (con huella) y un documento relevante.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('media-test')->put('obras/apus.png', $png);
        $this->artwork->images()->create(['path' => 'obras/apus.png']);
        Storage::disk('documents-test')->put('expedientes/declaracion.pdf', 'declaración firmada');
        $this->artwork->documents()->create(['category' => 'declaracion', 'title' => 'Declaración de autoría', 'is_relevant' => true])
            ->addVersion('expedientes/declaracion.pdf', 'declaracion.pdf', $this->artistUser);
        $this->artwork->documents()->create(['category' => 'otro', 'title' => 'Nota interna'])
            ->addVersion('expedientes/declaracion.pdf', 'nota.pdf', $this->artistUser);
    }

    public function test_issuing_builds_the_pdf_with_both_fingerprints(): void
    {
        $certificate = Certificate::issueFor($this->artwork, $this->admin);

        $this->assertSame($this->artwork->code.'-C1', $certificate->number);
        $this->assertSame(Certificate::STATUS_VIGENTE, $certificate->status);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $certificate->verification_token);
        $this->assertSame($this->admin->id, $certificate->issued_by);

        $snapshot = $certificate->snapshot;
        $this->assertSame(['codigo' => $this->artwork->code, 'titulo' => 'Apus al amanecer', 'artista' => 'Rosa Huamán', 'anio' => 2024,
            'tecnica' => 'Óleo', 'soporte' => 'Lienzo', 'alto_cm' => '80', 'ancho_cm' => '60.5', 'profundidad_cm' => null], $snapshot['obra']);
        $this->assertSame($this->artwork->images()->first()->sha256, $snapshot['imagen_principal_sha256']);
        $this->assertCount(1, $snapshot['documentos']); // solo los documentos relevantes
        $this->assertSame(hash('sha256', 'declaración firmada'), $snapshot['documentos'][0]['sha256']);
        $this->assertSame(Certificate::hashSnapshot($snapshot), $certificate->data_sha256);

        $pdf = Storage::disk('documents-test')->get($certificate->pdf_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(hash('sha256', $pdf), $certificate->pdf_sha256);
        $this->assertSame('https://arthub.test/verificar/'.$certificate->verification_token, $certificate->verificationUrl());
        $this->assertTrue($certificate->matchesCurrentArtwork());

        $event = $this->artwork->history()->where('type', 'certificado_emitido')->sole();
        $this->assertSame(['numero' => $certificate->number, 'huella_pdf' => $certificate->pdf_sha256], $event->payload);
        $this->assertSame($this->admin->id, $event->actor_id);
    }

    public function test_only_approved_or_published_artworks_are_certified(): void
    {
        $this->artwork->forceFill(['status' => Artwork::STATUS_EN_REVISION])->saveQuietly();

        $this->expectException(DomainException::class);
        Certificate::issueFor($this->artwork, $this->admin);
    }

    public function test_reissuing_replaces_the_current_certificate(): void
    {
        $first = Certificate::issueFor($this->artwork, $this->admin);
        $second = Certificate::issueFor($this->artwork->fresh(), $this->admin);

        $this->assertSame($this->artwork->code.'-C2', $second->number);
        $this->assertSame(Certificate::STATUS_REEMPLAZADO, $first->fresh()->status);
        $this->assertSame($second->id, $first->fresh()->replaced_by_id);
        $this->assertSame(1, $this->artwork->certificates()->where('status', 'vigente')->count());
        $this->assertTrue($this->artwork->fresh()->certificate->is($second));
        $this->assertSame(['numero' => $first->number, 'por' => $second->number],
            $this->artwork->history()->where('type', 'certificado_reemplazado')->sole()->payload);
    }

    public function test_revoking_needs_a_reason_and_is_final(): void
    {
        $certificate = Certificate::issueFor($this->artwork, $this->admin);

        try {
            $certificate->revoke($this->admin, '  ');
            $this->fail('Debió exigir un motivo.');
        } catch (InvalidArgumentException) {
        }

        $certificate->revoke($this->admin, 'Medidas mal registradas');
        $this->assertSame(Certificate::STATUS_ANULADO, $certificate->fresh()->status);
        $this->assertSame('Medidas mal registradas', $certificate->fresh()->revoke_reason);
        $this->assertNotNull($certificate->fresh()->revoked_at);
        $this->assertSame(['numero' => $certificate->number, 'motivo' => 'Medidas mal registradas'],
            $this->artwork->history()->where('type', 'certificado_anulado')->sole()->payload);

        $this->expectException(DomainException::class);
        $certificate->fresh()->revoke($this->admin, 'Otra vez');
    }

    public function test_detects_when_the_artwork_changed_after_issuing(): void
    {
        $certificate = Certificate::issueFor($this->artwork, $this->admin);

        $this->artwork->documents()->where('title', 'Nota interna')->first()->delete(); // no relevante: no afecta
        $this->assertTrue($certificate->fresh()->matchesCurrentArtwork());

        $this->artwork->update(['width_cm' => 61]);
        $this->assertFalse($certificate->fresh()->matchesCurrentArtwork());
    }

    public function test_portal_by_token_by_number_and_by_fingerprint(): void
    {
        $old = Certificate::issueFor($this->artwork, $this->admin);
        $current = Certificate::issueFor($this->artwork->fresh(), $this->admin);

        $this->getJson("/api/certificates/{$current->verification_token}")->assertOk()
            ->assertJsonPath('number', $current->number)
            ->assertJsonPath('status', 'vigente')
            ->assertJsonPath('data.obra.titulo', 'Apus al amanecer')
            ->assertJsonPath('data_sha256', $current->data_sha256)
            ->assertJsonPath('pdf_sha256', $current->pdf_sha256)
            ->assertJsonPath('pdf_url', url("/api/certificates/{$current->verification_token}/pdf"))
            ->assertJsonPath('artwork.id', $this->artwork->id)
            ->assertJsonMissingPath('verification_token');

        // Por número (se puede adivinar): sin PDF. El reemplazado remite al nuevo solo por su número.
        $this->getJson('/api/certificates/'.strtolower($old->number))->assertOk()
            ->assertJsonPath('status', 'reemplazado')
            ->assertJsonPath('replaced_by', $current->number)
            ->assertJsonPath('pdf_url', null);
        $this->getJson('/api/certificates/no-existe')->assertNotFound();

        $this->get("/api/certificates/{$current->verification_token}/pdf")->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->get("/api/certificates/{$current->number}/pdf")->assertNotFound();

        $this->getJson("/api/certificates/fingerprint/{$current->pdf_sha256}")->assertOk()
            ->assertJsonPath('match', true)
            ->assertJsonPath('certificate.number', $current->number)
            ->assertJsonPath('certificate.pdf_url', null);
        $this->getJson('/api/certificates/fingerprint/'.hash('sha256', 'otro archivo'))->assertOk()
            ->assertJsonPath('match', false);
        $this->getJson('/api/certificates/fingerprint/no-es-hash')->assertStatus(422);
    }

    public function test_unpublished_artworks_do_not_link_to_the_catalog(): void
    {
        $certificate = Certificate::issueFor($this->artwork, $this->admin);
        $this->artwork->forceFill(['status' => Artwork::STATUS_DESPUBLICADO])->saveQuietly();

        $this->getJson("/api/certificates/{$certificate->verification_token}")
            ->assertJsonPath('artwork.id', null)
            ->assertJsonPath('artwork.title', 'Apus al amanecer');
    }

    public function test_admin_issues_and_revokes_from_the_panel(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->admin);
        $manager = fn () => Livewire::test(ArtworkCertificatesRelationManager::class, ['ownerRecord' => $this->artwork, 'pageClass' => AdminEditArtwork::class]);

        $manager()->callAction(TestAction::make('issue')->table())->assertNotified();
        $certificate = $this->artwork->certificates()->sole();

        $manager()
            ->callAction(TestAction::make('revoke')->table($certificate), data: ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);
        $manager()
            ->callAction(TestAction::make('revoke')->table($certificate), data: ['reason' => 'Error en la ficha'])
            ->assertHasNoActionErrors();
        $this->assertSame('anulado', $certificate->fresh()->status);

        $this->artwork->forceFill(['status' => Artwork::STATUS_BORRADOR])->saveQuietly();
        $manager()->assertActionDisabled(TestAction::make('issue')->table());
    }

    public function test_artist_sees_certificates_but_cannot_issue_or_revoke(): void
    {
        $certificate = Certificate::issueFor($this->artwork, $this->admin);
        Filament::setCurrentPanel('artista');
        $this->actingAs($this->artistUser);

        Livewire::test(ArtworkCertificatesRelationManager::class, ['ownerRecord' => $this->artwork, 'pageClass' => ArtistViewArtwork::class])
            ->assertCanSeeTableRecords([$certificate])
            ->assertActionHidden(TestAction::make('issue')->table())
            ->assertActionHidden(TestAction::make('revoke')->table($certificate))
            ->assertActionVisible(TestAction::make('pdf')->table($certificate));
    }

    public function test_artwork_page_exposes_only_the_latest_certificate(): void
    {
        Certificate::issueFor($this->artwork, $this->admin);
        $current = Certificate::issueFor($this->artwork->fresh(), $this->admin);

        $this->getJson("/api/artworks/{$this->artwork->id}")
            ->assertJsonPath('certificate.verification_token', $current->verification_token)
            ->assertJsonPath('certificate.status', 'vigente');
    }
}
