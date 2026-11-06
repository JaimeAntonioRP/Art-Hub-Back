<?php

namespace Tests\Feature;

use App\Filament\Cliente\Resources\MyRequests\Pages\ListMyRequests;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use App\Notifications\VerifyClientEmail;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** Cuenta del cliente: «Mi cuenta» con el estado de sus solicitudes. */
class ClientAccountTest extends TestCase
{
    use RefreshDatabase;

    private function artwork(string $title = 'Apus'): Artwork
    {
        $artist = Artist::firstOrCreate(['slug' => 'rosa'], ['name' => 'Rosa Huamán', 'status' => Artist::STATUS_APROBADO]);

        return Artwork::create(['artist_id' => $artist->id, 'title' => $title, 'status' => Artwork::STATUS_PUBLICADO]);
    }

    private function request(Artwork $artwork, array $attrs)
    {
        return $artwork->commercialRequests()->create(['name' => 'Lucía', 'type' => 'intencion_compra', ...$attrs]);
    }

    public function test_registering_as_a_client_sends_the_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Lucía Quispe', 'email' => 'lucia@example.com', 'password' => 'secreta-123',
            'password_confirmation' => 'secreta-123', 'role' => 'comprador',
        ])->assertCreated();
        $this->postJson('/api/register', [
            'name' => 'Rosa Huamán', 'email' => 'rosa@example.com', 'password' => 'secreta-123',
            'password_confirmation' => 'secreta-123', 'role' => 'artista',
        ])->assertCreated();

        $client = User::where('email', 'lucia@example.com')->sole();
        Notification::assertSentTo($client, VerifyClientEmail::class, function (VerifyClientEmail $n) use ($client) {
            return str_contains($n->toMail($client)->actionUrl, '/mi-cuenta/email-verification/verify/');
        });
        Notification::assertNotSentTo(User::where('email', 'rosa@example.com')->sole(), VerifyClientEmail::class);
    }

    public function test_client_lands_in_my_account_and_must_verify_the_email(): void
    {
        $client = User::factory()->unverified()->create(['role' => User::ROLE_COMPRADOR, 'email' => 'lucia@example.com']);

        $this->post('/ingresar', ['email' => 'lucia@example.com', 'password' => 'password'])->assertRedirect(url('/mi-cuenta'));
        $this->get('/mi-cuenta/solicitudes')->assertRedirect(url('/mi-cuenta/email-verification/prompt'));

        $link = Filament::getPanel('cliente')->getVerifyEmailUrl($client);
        $this->get($link)->assertRedirect();
        $this->assertTrue($client->fresh()->hasVerifiedEmail());
        $this->get('/mi-cuenta/solicitudes')->assertOk();
    }

    public function test_client_sees_only_their_own_requests_without_internal_notes(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_COMPRADOR, 'email' => 'lucia@example.com']);
        $mine = $this->request($this->artwork('Apus'), ['email' => 'lucia@example.com', 'admin_notes' => 'Llamar el lunes']);
        $linked = $this->request($this->artwork('Niebla'), ['email' => 'otra@example.com', 'user_id' => $client->id]);
        $others = $this->request($this->artwork('Mercado'), ['email' => 'pedro@example.com']);

        Filament::setCurrentPanel('cliente');
        $this->actingAs($client);

        Livewire::test(ListMyRequests::class)
            ->assertCanSeeTableRecords([$mine, $linked])
            ->assertCanNotSeeTableRecords([$others])
            ->assertDontSee('Llamar el lunes');

        // Sin correo verificado, solo las que envió con la cuenta.
        $client->forceFill(['email_verified_at' => null])->save();
        Livewire::test(ListMyRequests::class)
            ->assertCanSeeTableRecords([$linked])
            ->assertCanNotSeeTableRecords([$mine, $others]);
    }

    public function test_each_role_stays_in_its_panel(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_COMPRADOR]);
        $artist = User::factory()->create(['role' => User::ROLE_ARTISTA]);

        $this->actingAs($client)->get('/admin')->assertForbidden();
        $this->actingAs($client)->get('/artista')->assertForbidden();
        $this->actingAs($artist)->get('/mi-cuenta/solicitudes')->assertForbidden();
    }
}
