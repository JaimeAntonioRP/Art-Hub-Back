<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
