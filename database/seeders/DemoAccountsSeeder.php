<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cuentas para probar la plataforma en local: un admin, un artista (ligado a
 * Augusto Garcia Peñalva, que tiene la colección de ejemplo) y un comprador.
 *
 * Nunca corre en producción. La contraseña es DEMO_PASSWORD (por defecto «arthub-demo»).
 *
 *   php artisan db:seed --class=DemoAccountsSeeder
 */
class DemoAccountsSeeder extends Seeder
{
    public const ACCOUNTS = [
        'admin@arthub.test'     => ['name' => 'Admin Demo', 'role' => User::ROLE_ADMIN],
        'artista@arthub.test'   => ['name' => 'Augusto Garcia Peñalva', 'role' => User::ROLE_ARTISTA],
        'comprador@arthub.test' => ['name' => 'Comprador Demo', 'role' => User::ROLE_COMPRADOR],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoAccountsSeeder no se ejecuta en producción.');

            return;
        }

        $password = Hash::make(env('DEMO_PASSWORD', 'arthub-demo'));

        foreach (self::ACCOUNTS as $email => $account) {
            $user = User::updateOrCreate(['email' => $email], [
                ...$account,
                'password'          => $password,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            if ($account['role'] === User::ROLE_ARTISTA) {
                // El artista de demostración administra la colección de ejemplo.
                $artist = Artist::where('slug', 'augusto-garcia-penalva')->first();
                if ($artist && (! $artist->user_id || $artist->user_id === $user->id)) {
                    $artist->update(['user_id' => $user->id]);
                }
            }
        }

        $this->command?->info('Cuentas de demostración listas (contraseña: DEMO_PASSWORD o «arthub-demo»):');
        $this->command?->table(['Correo', 'Rol'], collect(self::ACCOUNTS)->map(fn ($a, $email) => [$email, $a['role']])->values()->all());
    }
}
