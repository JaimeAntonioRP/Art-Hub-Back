<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/register', [
            'name'                  => 'Ana Quispe',
            'email'                 => 'ana@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            ...$data,
        ]);
    }

    public function test_registration_creates_a_buyer_by_default(): void
    {
        $this->register(['wallet_address' => '0x123'])
            ->assertCreated()
            ->assertJsonPath('user.role', 'comprador')
            ->assertJsonMissingPath('user.artist_status')
            ->assertJsonStructure(['token']);
    }

    public function test_artist_registration_creates_a_pending_profile(): void
    {
        $this->register(['role' => 'artista'])
            ->assertCreated()
            ->assertJsonPath('user.role', 'artista')
            ->assertJsonPath('user.artist_status', 'pendiente')
            ->assertJsonPath('user.artist_panel_url', url('/ingresar'));

        $artist = User::where('email', 'ana@example.com')->first()->artist;
        $this->assertSame('ana-quispe', $artist->slug);
        $this->assertSame('pendiente', $artist->status);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->register(['role' => 'admin'])->assertUnprocessable()->assertJsonValidationErrors('role');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'off@example.com', 'is_active' => false]);

        $this->postJson('/api/login', ['email' => 'off@example.com', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_deactivating_an_account_revokes_its_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $user->update(['is_active' => false]);

        $this->assertSame(0, $user->tokens()->count());
        $this->getJson('/api/me', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
    }

    public function test_forgot_password_sends_a_link_to_the_frontend(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://arthub.test']);
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'ana@example.com'])->assertOk();
        // Un correo que no existe recibe la misma respuesta.
        $this->postJson('/api/forgot-password', ['email' => 'nadie@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://arthub.test/restablecer?token=')
                && str_contains($url, 'email=ana%40example.com');
        });
    }

    public function test_reset_password_changes_it_and_closes_sessions(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $user->createToken('vieja');
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'nueva-clave-1',
            'password_confirmation' => 'nueva-clave-1',
        ])->assertOk();

        $this->assertTrue(Hash::check('nueva-clave-1', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'otra-clave-22',
            'password_confirmation' => 'otra-clave-22',
        ])->assertUnprocessable();
    }

    public function test_user_updates_profile_and_needs_current_password_to_change_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->putJson('/api/me', ['name' => 'Nuevo nombre'])->assertOk()->assertJsonPath('name', 'Nuevo nombre');

        $this->putJson('/api/me', ['password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1'])
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->putJson('/api/me', [
            'current_password' => 'password',
            'password' => 'nueva-clave-1',
            'password_confirmation' => 'nueva-clave-1',
        ])->assertOk();
        $this->assertTrue(Hash::check('nueva-clave-1', $user->fresh()->password));
    }

    public function test_login_is_rate_limited(): void
    {
        foreach (range(1, 6) as $i) {
            $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'mala'])->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'mala'])->assertTooManyRequests();
    }
}
