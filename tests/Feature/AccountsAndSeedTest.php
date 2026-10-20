<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsAndSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_buyer_and_ignores_wallet(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'Ana',
            'email'                 => 'ana@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'role'                  => 'admin',
            'wallet_address'        => '0x123',
        ])->assertCreated()->assertJsonPath('user.role', 'comprador');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'off@example.com', 'is_active' => false]);

        $this->postJson('/api/login', ['email' => 'off@example.com', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_seeder_is_idempotent_and_keeps_edits(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Artist::count(), Artwork::count()];

        $artwork = Artwork::first();
        $artwork->update(['price' => 1, 'title' => 'Título editado']);
        User::where('role', 'admin')->update(['password' => 'hash-editado']);

        $this->seed(DatabaseSeeder::class);

        // La obra renombrada se vuelve a crear con su título original; nada se borra ni se sobrescribe.
        $this->assertSame([$counts[0], $counts[1], $counts[2] + 1], [User::count(), Artist::count(), Artwork::count()]);
        $this->assertSame('1.00', $artwork->fresh()->price);
        $this->assertSame('hash-editado', User::where('role', 'admin')->value('password'));
        $this->assertSame(0, Certificate::count());
    }

    public function test_admin_panel_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'admin')->first();
        $artwork = Artwork::first();
        Certificate::create(['artwork_id' => $artwork->id, 'verification_token' => 'tok', 'status' => 'vigente']);

        $this->actingAs($admin);
        foreach (['/admin', '/admin/obras', '/admin/obras/create', "/admin/obras/{$artwork->id}/edit",
                  '/admin/artistas', '/admin/usuarios', '/admin/certificados'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->patch("/admin/certificados/{$artwork->certificate->id}/toggle")->assertRedirect();
        $this->assertSame('anulado', $artwork->certificate->fresh()->status);
    }

    public function test_admin_panel_updates_artwork_availability(): void
    {
        $this->seed(DatabaseSeeder::class);
        $artwork = Artwork::first();

        $this->actingAs(User::where('role', 'admin')->first())
            ->put("/admin/obras/{$artwork->id}", [
                'title'        => $artwork->title,
                'artist_name'  => $artwork->artist_name,
                'dimensions'   => '50 x 70 cm',
                'status'       => 'publicado',
                'availability' => 'reservada',
            ])->assertRedirect(route('admin.obras.index'));

        $artwork->refresh();
        $this->assertSame('reservada', $artwork->availability);
        $this->assertSame('50 × 70 cm', $artwork->dimensions);
    }
}
