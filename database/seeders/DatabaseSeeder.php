<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea solo lo que falta: nunca borra registros ni sobrescribe lo que se editó desde los paneles.
 * El contenido de ejemplo es la colección de Augusto Garcia Peñalva; no se cargan otros
 * artistas (eran artistas reales con biografías inventadas).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedAugustoCollection();
        $this->call(SiteContentSeeder::class);

        // En local, además, cuentas para probar cada rol (nunca en producción).
        if (app()->isLocal()) {
            $this->call(DemoAccountsSeeder::class);
        }
    }

    private function seedAdmin(): void
    {
        $email = config('app.admin.email');
        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = config('app.admin.password');
        if (! $password) {
            if (app()->isProduction()) {
                $this->command?->warn('ADMIN_PASSWORD no está definido: no se creó el administrador.');

                return;
            }
            $password = 'password';
        }

        User::create([
            'name'      => 'Administrador Arthub',
            'email'     => $email,
            'password'  => Hash::make($password),
            'role'      => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function seedAugustoCollection(): void
    {
        $artist = Artist::firstOrCreate(
            ['slug' => 'augusto-garcia-penalva'],
            [
                'name'              => 'Augusto Garcia Peñalva',
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1985,
                'specialty'         => 'Arte contemporáneo andino, Pintura y escultura 3D',
                'bio'               => 'Augusto Garcia Peñalva es un artista cusqueño que fusiona la tradición pictórica andina con herramientas digitales y modelado 3D. Su obra explora la identidad, la maternidad, la naturaleza de los Apus y la vida comunitaria del Cusco a través de una paleta cálida y composiciones íntimas. Varias de sus piezas cuentan con una versión tridimensional explorable.',
                'profile_image_url' => '/equipo/augusto.png',
                'featured'          => true,
                'status'            => Artist::STATUS_APROBADO,
            ],
        );

        // [slug, título, precio USD, tiene3D, descripción, técnica, año, dimensiones (alto × ancho)]
        $collection = [
            ['confidencias-al-viento',      'Confidencias al Viento',        8200,  true,  'Dos figuras comparten un secreto mientras el viento andino arrastra sus palabras hacia los cerros. Óleo de tonos cálidos con versión 3D explorable.',                              'Óleo sobre lienzo · Modelado 3D',          2023, '90 × 120 cm'],
            ['ecos-de-la-montana',          'Ecos de la Montaña',            9600,  true,  'La montaña responde al canto de quienes la habitan. Composición que captura la resonancia espiritual del paisaje cusqueño. Incluye modelo 3D.',                                 'Óleo sobre lienzo · Modelado 3D',          2022, '100 × 130 cm'],
            ['el-calor-de-la-hermandad',    'El Calor de la Hermandad',      6400, false, 'Retrato de la unión comunitaria andina, donde el abrazo colectivo es refugio frente al frío de las alturas.',                                                                     'Técnica mixta andina',                     2021, '80 × 100 cm'],
            ['el-latido-del-reposo',        'El Latido del Reposo',          7800,  true,  'El descanso como acto sagrado. Una figura en reposo cuyo pulso late al ritmo de la tierra. Versión tridimensional disponible.',                                                  'Óleo sobre lienzo · Modelado 3D',          2023, '85 × 110 cm'],
            ['el-primer-fruto-de-la-tierra','El Primer Fruto de la Tierra',  7100, false, 'Celebración de la primera cosecha, ofrenda y gratitud a la Pachamama por la abundancia.',                                                                                       'Acrílico sobre tela',                      2020, '70 × 90 cm'],
            ['el-reposo-del-caminante',     'El Reposo del Caminante',       6900, false, 'El viajero andino encuentra una pausa en su largo camino por los senderos del Valle Sagrado.',                                                                                   'Acrílico sobre tela',                      2021, '75 × 95 cm'],
            ['el-tejido-sagrado',           'El Tejido Sagrado',            10400,  false, 'Las manos de la tejedora entrelazan memoria, identidad y cosmovisión en cada hilo del telar ancestral.',                                                                          'Óleo y pan de oro sobre lienzo',           2022, '100 × 100 cm'],
            ['furia-sangre-y-tradicion',    'Furia, Sangre y Tradición',    11800,  false, 'La intensidad del rito y la danza guerrera andina, donde la tradición se vive con el cuerpo y el alma.',                                                                          'Técnica mixta andina',                     2023, '110 × 140 cm'],
            ['hijas-del-apu',               'Hijas del Apu',                 9200,  true,  'Las mujeres protegidas por el espíritu de la montaña sagrada. Obra central de la serie, con modelo 3D.',                                                                          'Óleo sobre lienzo · Modelado 3D',          2022, '95 × 120 cm'],
            ['madre-cusquena',              'Madre Cusqueña',               12500,  true,  'La maternidad andina retratada con ternura y fuerza. Pieza emblemática de la colección, explorable en 3D y AR.',                                                                  'Óleo sobre lienzo · Modelado 3D',          2024, '120 × 150 cm'],
            ['melancolia-entre-las-cuerdas','Melancolía entre las Cuerdas',  7400, false, 'El músico solitario y su instrumento comparten una pena silenciosa bajo el cielo del altiplano.',                                                                                 'Acrílico sobre tela',                      2021, '80 × 100 cm'],
            ['ofrenda-de-la-tierra',        'Ofrenda de la Tierra',          6800, false, 'Manos campesinas ofrecen los dones del suelo en un gesto de reciprocidad con la naturaleza.',                                                                                     'Técnica mixta andina',                     2020, '70 × 90 cm'],
            ['piedad-cusquena',             'Piedad Cusqueña',              13200,  false, 'Reinterpretación andina de la piedad clásica, fusionando el dolor universal con la espiritualidad del Cusco.',                                                                    'Óleo sobre lienzo',                        2024, '130 × 160 cm'],
            ['pureza-al-descubierto',       'Pureza al Descubierto',         8600, false, 'Estudio de la inocencia y la verdad sin máscaras, en una composición de luz delicada.',                                                                                           'Acrílico sobre lienzo',                    2022, '80 × 100 cm'],
            ['refugio-de-paz',              'Refugio de Paz',                7000, false, 'Un rincón de serenidad en medio del bullicio del mundo, donde el alma encuentra calma.',                                                                                          'Acrílico sobre tela',                      2021, '75 × 95 cm'],
            ['semilla-de-vida',             'Semilla de Vida',               6600, false, 'El origen de todo: la pequeña semilla que guarda en sí la promesa de la existencia.',                                                                                             'Técnica mixta andina',                     2020, '60 × 80 cm'],
            ['semilla-y-raiz',              'Semilla y Raíz',                6700, false, 'Diálogo entre lo que germina y lo que sostiene; la conexión profunda con los ancestros.',                                                                                         'Técnica mixta andina',                     2021, '65 × 85 cm'],
            ['sueno-entre-apus',            'Sueño entre Apus',              9900,  false, 'Un descanso onírico abrazado por las montañas tutelares del Cusco, guardianas del sueño.',                                                                                        'Óleo sobre lienzo',                        2023, '100 × 120 cm'],
            ['vuelo-compartido',            'Vuelo Compartido',              8800, false, 'Dos espíritus que se elevan juntos, símbolo de la libertad y el acompañamiento mutuo.',                                                                                           'Acrílico sobre lienzo',                    2022, '90 × 110 cm'],
        ];

        foreach ($collection as [$slug, $title, $price, $has3d, $desc, $technique, $year, $dimensions]) {
            if ($artist->artworks()->where('title', $title)->exists()) {
                continue;
            }

            Artwork::create([
                'artist_id'    => $artist->id,
                'title'        => $title,
                'price'        => $price,
                'currency'     => 'USD',
                'description'  => $desc,
                'technique'    => $technique,
                'year'         => $year,
                'dimensions'   => $dimensions,
                'image_url'    => '/obras/'.$slug.'.jpg',
                'model_3d_url' => $has3d ? '/3d/'.$slug.'.glb' : null,
                'status'       => Artwork::STATUS_PUBLICADO,
                'availability' => 'disponible',
            ]);
        }
    }
}
