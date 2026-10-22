<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Login único: una sola página y cada rol sigue a su lugar. */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://arthub.test']);
    }

    private function user(string $role, array $attrs = []): User
    {
        return User::factory()->create(['role' => $role, 'email' => "{$role}@example.com", ...$attrs]);
    }

    public function test_each_role_lands_in_its_place(): void
    {
        $this->get('/ingresar')->assertOk()->assertSee('Ingresar')->assertSee('https://arthub.test/recuperar');

        foreach ([
            User::ROLE_ADMIN     => url('/admin'),
            User::ROLE_ARTISTA   => url('/artista'),
            User::ROLE_COMPRADOR => 'https://arthub.test/obras',
        ] as $role => $home) {
            $this->user($role);
            $this->post('/ingresar', ['email' => "{$role}@example.com", 'password' => 'password'])->assertRedirect($home);
            $this->assertAuthenticated();
            $this->post('/salir')->assertRedirect('/ingresar');
            $this->assertGuest();
        }
    }

    public function test_panels_send_guests_to_the_single_login_and_keep_the_link(): void
    {
        $this->get('/admin')->assertRedirect('/ingresar');
        $this->get('/artista/obras')->assertRedirect('/ingresar');
        $this->get('/admin/login')->assertNotFound();
        $this->get('/artista/login')->assertNotFound();

        // El enlace de un correo se respeta si es del panel del usuario…
        $this->user(User::ROLE_ARTISTA);
        $this->get('/artista/obras');
        $this->post('/ingresar', ['email' => 'artista@example.com', 'password' => 'password'])
            ->assertRedirect(url('/artista/obras'));
        $this->post('/salir');

        // …pero un artista nunca termina en el panel del admin.
        $this->get('/admin/obras');
        $this->post('/ingresar', ['email' => 'artista@example.com', 'password' => 'password'])
            ->assertRedirect(url('/artista'));
    }

    public function test_wrong_password_and_inactive_accounts_are_rejected(): void
    {
        $this->user(User::ROLE_ADMIN);
        $this->user(User::ROLE_ARTISTA, ['is_active' => false]);

        $this->from('/ingresar')->post('/ingresar', ['email' => 'admin@example.com', 'password' => 'mala'])
            ->assertRedirect('/ingresar')
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
        $this->post('/ingresar', ['email' => 'artista@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada. Escríbenos si crees que es un error.']);
        $this->assertGuest();
    }

    public function test_failed_attempts_are_limited(): void
    {
        $this->user(User::ROLE_ADMIN);

        foreach (range(1, 6) as $_) {
            $this->post('/ingresar', ['email' => 'admin@example.com', 'password' => 'mala']);
        }
        $this->post('/ingresar', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('email'));
    }

    public function test_signed_in_users_skip_the_form(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))->get('/ingresar')->assertRedirect(url('/admin'));
    }

    public function test_panel_logout_returns_to_the_single_login(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post(route('filament.admin.auth.logout'))
            ->assertRedirect('/ingresar');
        $this->assertGuest();
    }
}
