<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_demo_accounts_exist_only_outside_production(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoAccountsSeeder::class);

        $artist = User::where('email', 'artista@arthub.test')->sole();
        $this->assertSame(User::ROLE_ARTISTA, $artist->role);
        $this->assertTrue(Hash::check('arthub-demo', $artist->password));
        $this->assertSame('augusto-garcia-penalva', $artist->artist->slug); // administra la colección de ejemplo
        $this->assertSame(User::ROLE_ADMIN, User::where('email', 'admin@arthub.test')->value('role'));

        User::whereIn('email', array_keys(DemoAccountsSeeder::ACCOUNTS))->delete();
        $this->app['env'] = 'production';
        (new DemoAccountsSeeder)->run(); // directo: db:seed pediría confirmación en producción

        $this->assertSame(0, User::whereIn('email', array_keys(DemoAccountsSeeder::ACCOUNTS))->count());
    }
}
