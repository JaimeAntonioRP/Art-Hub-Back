<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Se ejecuta en cada deploy, así que solo crea lo que falta:
 * nunca borra registros ni sobrescribe lo que se editó desde los paneles.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedArtists();
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

    private function seedArtists(): void
    {
        $artists = [
            [
                'name'              => 'Mariano Fuentes Lira',
                'slug'              => 'mariano-fuentes-lira',
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1920,
                'death_year'        => 1995,
                'specialty'         => 'Pintura al óleo, Barroco Andino',
                'bio'               => 'Mariano Fuentes Lira fue el gran cronista visual del Cusco colonial y festivo. Nacido en el barrio de San Blas, estudió en la Escuela Regional de Bellas Artes del Cusco bajo la tutela de Francisco González Gamarra. Su obra documenta con rigor y belleza las procesiones, fiestas patronales y paisajes del sur andino peruano. Maestro de la composición monumental, sus óleos de gran formato capturan la esencia del barroco andino fusionando la técnica académica europea con la iconografía incaica. Participó en exposiciones en Lima, Buenos Aires y Madrid durante las décadas de 1960 y 1970, recibiendo el Premio Nacional de Pintura en 1968. Su legado influye directamente en tres generaciones de pintores cusqueños.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=800',
                'featured'          => true,
            ],
            [
                'name'              => 'Edilberto Mérida',
                'slug'              => 'edilberto-merida',
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1952,
                'death_year'        => null,
                'specialty'         => 'Técnica mixta, Arte religioso contemporáneo',
                'bio'               => 'Edilberto Mérida es uno de los artistas vivos más reconocidos de la escuela cusqueña contemporánea. Formado en la Escuela de Bellas Artes del Cusco, desarrolló una técnica personal que combina el pan de oro, pigmentos minerales y acrílicos sobre madera envejecida. Su serie sobre la Virgen del Carmen de Paucartambo es considerada patrimonio vivo de la festividad declarada por la UNESCO. Ha expuesto en galerías de Nueva York, París y Tokio, y sus obras forman parte de colecciones privadas en más de 20 países. En 2015 recibió la Orden del Sol del Gobierno Peruano por su contribución a la difusión del arte andino.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=800',
                'featured'          => true,
            ],
            [
                'name'              => 'Hilario Mendívil',
                'slug'              => 'hilario-mendivil',
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1929,
                'death_year'        => 1977,
                'specialty'         => 'Escultura en yeso de papa, Arte religioso popular',
                'bio'               => 'Hilario Mendívil revolucionó la imaginería religiosa andina con su característica técnica de cuellos alargados y expresiones serenas, inspirada en la deidad inca Wiracocha. Trabajó principalmente con yeso de papa (masa de papa seca) mezclado con paja y cola de carpintero, creando figuras de una elegancia etérea única en el mundo. Su taller en el barrio de San Blas se convirtió en referente del arte popular cusqueño. Fue reconocido por el Ministerio de Educación como Tesoro Humano Vivo. Tras su fallecimiento, su esposa Georgina Arizapana e hijos continuaron el estilo que hoy lleva el nombre de "escuela mendivil".',
                'profile_image_url' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=800',
                'featured'          => true,
            ],
            [
                'name'              => 'Maximiliana Palomino',
                'slug'              => 'maximiliana-palomino',
                'origin'            => 'Pisaq, Cusco, Perú',
                'birth_year'        => 1968,
                'death_year'        => null,
                'specialty'         => 'Técnica mixta, Simbolismo andino, Pigmentos minerales',
                'bio'               => 'Maximiliana Palomino es una de las voces más originales del arte peruano contemporáneo. Nacida en Pisaq, en el corazón del Valle Sagrado, creció inmersa en las tradiciones textiles y ceremoniales de su comunidad quechua. Estudió en Lima y luego en la Escuela de Arte de Florencia con una beca Fulbright. Su obra fusiona la geometría sagrada andina con el expresionismo abstracto occidental, usando pigmentos minerales recolectados personalmente en las montañas del Cusco. Ha sido incluida en las bienales de São Paulo (2010), Venecia (2017) y en la colección permanente del Museo de Arte de Lima (MALI).',
                'profile_image_url' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=800',
                'featured'          => true,
            ],
            [
                'name'              => 'Victor Delfín Quiroga',
                'slug'              => 'victor-delfin-quiroga',
                'origin'            => 'Ica, Perú',
                'birth_year'        => 1927,
                'death_year'        => null,
                'specialty'         => 'Escultura, Pintura al óleo, Paisajismo andino',
                'bio'               => 'Victor Delfín es el escultor más importante del Perú contemporáneo. Su monumental obra El Beso en el Parque del Amor de Miraflores, Lima, es el ícono visual más fotografiado del país. Aunque nacido en Ica, su obra pictórica gira en torno al paisaje andino del sur peruano, especialmente los nevados y ríos del Cusco que visita cada año. Estudió en la Escuela de Bellas Artes de Lima y perfeccionó su técnica en Italia. Ha recibido el Premio Nacional de Cultura, la Palma Magisterial y la Medalla de Honor del Congreso. A sus 97 años continúa activo en su taller de Barranco.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800',
                'featured'          => true,
            ],
            [
                'name'              => 'Joaquín López Antay',
                'slug'              => 'joaquin-lopez-antay',
                'origin'            => 'Ayacucho, Perú',
                'birth_year'        => 1897,
                'death_year'        => 1981,
                'specialty'         => 'Retablo ayacuchano, Arte popular narrativo',
                'bio'               => 'Joaquín López Antay transformó el antiguo "san marcos" colonial en el retablo ayacuchano moderno: cajas de madera con escenas narrativas en dos pisos que documentan la vida campesina, religiosa y mítica de los Andes. Aprendió el oficio de su abuela y lo practicó durante 80 años, produciendo más de 3,000 piezas. En 1975 recibió el Premio Nacional de Cultura, decisión que generó el famoso "debate del artesano" sobre los límites entre arte y artesanía en el Perú. Sus retablos están en los museos Smithsonian (Washington), Etnológico (Madrid) y en la colección del MALI. Declarado Patrimonio Cultural Vivo por el Gobierno Peruano.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=800',
                'featured'          => false,
            ],
            [
                'name'              => 'Antonio Olave Palomino',
                'slug'              => 'antonio-olave-palomino',
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1888,
                'death_year'        => 1959,
                'specialty'         => 'Pintura costumbrista, Óleo sobre tela',
                'bio'               => 'Antonio Olave Palomino fue el cronista cotidiano del Cusco de la primera mitad del siglo XX. Sus pinturas al óleo registran con precisión documental los mercados, calles empedradas y tipos humanos del Cusco andino antes de la masificación turística. Estudió en Lima en la Academia de Bellas Artes y regresó al Cusco para dedicar su obra a la memoria colectiva de su ciudad. Su influencia en la pintura costumbrista cusqueña es comparable a la de Pancho Fierro en Lima. Colecciones importantes en el Museo Inka de la UNSAAC y en el Museo de Arte de Lima.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=800',
                'featured'          => false,
            ],
            [
                'name'              => 'Marcos Zapata',
                'slug'              => 'marcos-zapata',
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1710,
                'death_year'        => 1773,
                'specialty'         => 'Pintura colonial, Escuela Cusqueña del siglo XVIII',
                'bio'               => 'Marcos Zapata es el pintor más prolífico y reconocido de la Escuela Cusqueña del siglo XVIII. Discípulo de Basilio de Santa Cruz, desarrolló un estilo propio que sintetiza la técnica flamenca, la iconografía católica y los elementos andinos precolombinos. Su obra más famosa, La Última Cena (1753) en la Catedral del Cusco, muestra a Cristo y los apóstoles comiendo cuy, chicha y tamales: una afirmación magistral de la identidad andina dentro del canon religioso occidental. Pintó los 50 lienzos de la vida de la Virgen para la Catedral del Cusco, el mayor ciclo iconográfico colonial de América del Sur. Sus obras se conservan en iglesias, museos y colecciones privadas de todo el mundo.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1519345182560-3f2917c472ef?w=800',
                'featured'          => true,
            ],
            [
                'name'              => 'Juan de Espinosa Medrano',
                'slug'              => 'juan-de-espinosa-medrano',
                'origin'            => 'Calcauso, Apurímac, Perú',
                'birth_year'        => 1629,
                'death_year'        => 1688,
                'specialty'         => 'Pintura colonial, Retórica, Barroco andino temprano',
                'bio'               => 'Conocido como "El Lunarejo", Juan de Espinosa Medrano fue el mayor intelectual del barroco colonial peruano: teólogo, predicador, dramaturgo y pintor. Estudió en el Colegio de San Antonio Abad del Cusco donde luego sería catedrático. Sus sermones en quechua y latín son documentos únicos del pensamiento andino colonial. Su producción pictórica, aunque escasa, muestra un dominio del claroscuro y la composición comparable a los maestros europeos de su época. Las obras que se le atribuyen combinan la severidad tenebrista con una sensibilidad colorista propia del mundo andino. Su figura inspiró la novela "El zorro de arriba y el zorro de abajo" de José María Arguedas.',
                'profile_image_url' => 'https://images.unsplash.com/photo-1542909168-82c3e7fdca5c?w=800',
                'featured'          => false,
            ],
        ];

        foreach ($artists as $data) {
            $data['profile_image_url'] = '/artistas/'.$data['slug'].'.jpg';
            $data['status'] = Artist::STATUS_APROBADO;
            Artist::firstOrCreate(['slug' => $data['slug']], $data);
        }
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
