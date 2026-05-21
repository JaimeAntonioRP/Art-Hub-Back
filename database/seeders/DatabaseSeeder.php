<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedArtists();
        $this->seedArtworks();
        $this->seedJaimeCollection();
    }

    private function seedJaimeCollection(): void
    {
        // elimina el artista antiguo si quedó del seeder anterior
        Artist::where('slug', 'jaime-antonio-rodriguez')->delete();

        $artistName = 'Augusto Garcia Peñalva';

        Artist::updateOrCreate(
            ['slug' => 'augusto-garcia-penalva'],
            [
                'name'              => $artistName,
                'origin'            => 'Cusco, Perú',
                'birth_year'        => 1985,
                'death_year'        => null,
                'specialty'         => 'Arte contemporáneo andino, Pintura y escultura 3D',
                'bio'               => 'Augusto Garcia Peñalva es un artista cusqueño que fusiona la tradición pictórica andina con herramientas digitales y modelado 3D. Su obra explora la identidad, la maternidad, la naturaleza de los Apus y la vida comunitaria del Cusco a través de una paleta cálida y composiciones íntimas. Cada pieza está certificada biométricamente y varias cuentan con su versión tridimensional explorable en realidad aumentada.',
                'profile_image_url' => '/equipo/augusto.png',
                'featured'          => true,
            ],
        );

        // [slug, título, precio, match%, blockchain, tiene3D, descripción]
        $collection = [
            ['confidencias-al-viento', 'Confidencias al Viento', 8200, 97.4, true, true, 'Dos figuras comparten un secreto mientras el viento andino arrastra sus palabras hacia los cerros. Óleo de tonos cálidos con versión 3D explorable.'],
            ['ecos-de-la-montana', 'Ecos de la Montaña', 9600, 96.1, true, true, 'La montaña responde al canto de quienes la habitan. Composición que captura la resonancia espiritual del paisaje cusqueño. Incluye modelo 3D.'],
            ['el-calor-de-la-hermandad', 'El Calor de la Hermandad', 6400, 92.8, false, false, 'Retrato de la unión comunitaria andina, donde el abrazo colectivo es refugio frente al frío de las alturas.'],
            ['el-latido-del-reposo', 'El Latido del Reposo', 7800, 95.5, true, true, 'El descanso como acto sagrado. Una figura en reposo cuyo pulso late al ritmo de la tierra. Versión tridimensional disponible.'],
            ['el-primer-fruto-de-la-tierra', 'El Primer Fruto de la Tierra', 7100, 93.6, false, false, 'Celebración de la primera cosecha, ofrenda y gratitud a la Pachamama por la abundancia.'],
            ['el-reposo-del-caminante', 'El Reposo del Caminante', 6900, 91.2, false, false, 'El viajero andino encuentra una pausa en su largo camino por los senderos del Valle Sagrado.'],
            ['el-tejido-sagrado', 'El Tejido Sagrado', 10400, 98.2, true, false, 'Las manos de la tejedora entrelazan memoria, identidad y cosmovisión en cada hilo del telar ancestral.'],
            ['furia-sangre-y-tradicion', 'Furia, Sangre y Tradición', 11800, 96.9, true, false, 'La intensidad del rito y la danza guerrera andina, donde la tradición se vive con el cuerpo y el alma.'],
            ['hijas-del-apu', 'Hijas del Apu', 9200, 97.0, true, true, 'Las mujeres protegidas por el espíritu de la montaña sagrada. Obra central de la serie, con modelo 3D.'],
            ['madre-cusquena', 'Madre Cusqueña', 12500, 99.1, true, true, 'La maternidad andina retratada con ternura y fuerza. Pieza emblemática de la colección, explorable en 3D y AR.'],
            ['melancolia-entre-las-cuerdas', 'Melancolía entre las Cuerdas', 7400, 94.3, false, false, 'El músico solitario y su instrumento comparten una pena silenciosa bajo el cielo del altiplano.'],
            ['ofrenda-de-la-tierra', 'Ofrenda de la Tierra', 6800, 92.0, false, false, 'Manos campesinas ofrecen los dones del suelo en un gesto de reciprocidad con la naturaleza.'],
            ['piedad-cusquena', 'Piedad Cusqueña', 13200, 98.7, true, false, 'Reinterpretación andina de la piedad clásica, fusionando el dolor universal con la espiritualidad del Cusco.'],
            ['pureza-al-descubierto', 'Pureza al Descubierto', 8600, 95.0, false, false, 'Estudio de la inocencia y la verdad sin máscaras, en una composición de luz delicada.'],
            ['refugio-de-paz', 'Refugio de Paz', 7000, 93.1, false, false, 'Un rincón de serenidad en medio del bullicio del mundo, donde el alma encuentra calma.'],
            ['semilla-de-vida', 'Semilla de Vida', 6600, 91.8, false, false, 'El origen de todo: la pequeña semilla que guarda en sí la promesa de la existencia.'],
            ['semilla-y-raiz', 'Semilla y Raíz', 6700, 92.5, false, false, 'Diálogo entre lo que germina y lo que sostiene; la conexión profunda con los ancestros.'],
            ['sueno-entre-apus', 'Sueño entre Apus', 9900, 96.4, true, false, 'Un descanso onírico abrazado por las montañas tutelares del Cusco, guardianas del sueño.'],
            ['vuelo-compartido', 'Vuelo Compartido', 8800, 95.7, false, false, 'Dos espíritus que se elevan juntos, símbolo de la libertad y el acompañamiento mutuo.'],
        ];

        foreach ($collection as [$slug, $title, $price, $match, $hasChain, $has3d, $desc]) {
            $artwork = Artwork::updateOrCreate(
                ['title' => $title],
                [
                    'artist_name'  => $artistName,
                    'price'        => $price,
                    'description'  => $desc,
                    'image_url'    => '/obras/'.$slug.'.jpg',
                    'model_3d_url' => $has3d ? '/3d/'.$slug.'.glb' : null,
                    'status'       => 'available',
                ],
            );

            Certificate::updateOrCreate(
                ['artwork_id' => $artwork->id],
                [
                    'biometric_hash'   => hash('sha256', $artwork->title.'|'.$artistName),
                    'match_percentage' => $match,
                    'blockchain_tx_id' => $hasChain ? '0x'.bin2hex(random_bytes(32)) : null,
                    'contract_address' => $hasChain ? '0x'.bin2hex(random_bytes(20)) : null,
                ],
            );
        }
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'name'           => 'Administrador Arthub',
                'email'          => 'admin@arthub.pe',
                'password'       => Hash::make('password'),
                'role'           => 'admin',
                'wallet_address' => null,
            ],
            [
                'name'           => 'Carla Montoya Quispe',
                'email'          => 'carla.montoya@arthub.pe',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0x9C2A8F77D1B4E5A60F2c2A1B7CB6A0BD13E4F112',
            ],
            [
                'name'           => 'Mateo Coleccionista',
                'email'          => 'mateo@arthub.pe',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0x7F11C3B92E9AE5631F8C0D5b81e7d442Aa12B0Ee',
            ],
            [
                'name'           => 'Sofia Bergmann',
                'email'          => 'sofia.bergmann@gallery-zurich.ch',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0xA4c0D17F8bE2931C05Ab77d5E3c0B4A18FF92D3c',
            ],
            [
                'name'           => 'Alejandro Ruiz Vargas',
                'email'          => 'alejandro.ruiz@fondo-capital.mx',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0xB81e7D3c2F44A0c55E9CB6A17D8F20B3C4E5F6A7',
            ],
            [
                'name'           => 'Priya Nair',
                'email'          => 'priya.nair@artfund-singapore.sg',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0xC92bA4E31D7F5B6C8E0A9B2C3D4E5F6A7B8C9D0E',
            ],
            [
                'name'           => 'Thomas Leclerc',
                'email'          => 'thomas.leclerc@patrimoine-paris.fr',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0xD03cB5F42E8A7C9D0E1F2A3B4C5D6E7F8A9B0C1D',
            ],
            [
                'name'           => 'Isabella Ferretti',
                'email'          => 'i.ferretti@collezioni-roma.it',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => null,
            ],
            [
                'name'           => 'James Whitmore',
                'email'          => 'j.whitmore@miami-arts.us',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0xE14dC6A53F9B8D0E2F3A4B5C6D7E8F9A0B1C2D3E',
            ],
            [
                'name'           => 'Lin Wei',
                'email'          => 'lin.wei@artventure-hk.com',
                'password'       => Hash::make('password'),
                'role'           => 'investor',
                'wallet_address' => '0xF25eD7B64A0C9E1F3A4B5C6D7E8F9A0B1C2D3E4F',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(['email' => $data['email']], $data);
        }
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
            Artist::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }

    private function seedArtworks(): void
    {
        $catalog = [
            [
                'title'          => 'Procesion del Senor de los Temblores',
                'artist_name'    => 'Mariano Fuentes Lira',
                'price'          => 18500.00,
                'description'    => 'Oleo sobre lienzo de gran formato que retrata la procesion central del Cusco con detalle minucioso en los mantos bordados y la arquitectura colonial de la Plaza de Armas. Obra de referencia del barroco andino del siglo XX.',
                'image_url'      => 'https://images.unsplash.com/photo-1578926375605-eaf7559b1458?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 98.42,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Virgen del Carmen de Paucartambo',
                'artist_name'    => 'Edilberto Mérida',
                'price'          => 12750.00,
                'description'    => 'Pieza de la escuela cusquena contemporanea inspirada en la festividad mariana de Paucartambo. Tecnica mixta con pan de oro y pigmentos minerales. Certificada por el Ministerio de Cultura del Peru.',
                'image_url'      => 'https://images.unsplash.com/photo-1582555172866-f73bb12a2ab3?w=1200',
                'model_3d_url'   => 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',
                'status'         => 'available',
                'match'          => 96.10,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Retablo de Ollantaytambo',
                'artist_name'    => 'Hilario Mendívil',
                'price'          => 22300.00,
                'description'    => 'Retablo en madera policromada que recrea escenas del Valle Sagrado y la cosmovision andina. Trabajo en yeso de papa con acabado en esmalte. Pieza unica de alto valor etnografico.',
                'image_url'      => 'https://images.unsplash.com/photo-1577083552431-6e5fd01988ec?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 99.05,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Pachamama bajo la Cruz del Sur',
                'artist_name'    => 'Maximiliana Palomino',
                'price'          => 9800.00,
                'description'    => 'Tecnica mixta sobre tabla con pigmentos minerales del Valle. Sintesis entre lo prehispanico y lo barroco andino. La obra explora la dualidad cosmologica inca a traves de simbolos geometricos y figuras zoomorfas.',
                'image_url'      => 'https://images.unsplash.com/photo-1549887534-1541e9326642?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 94.78,
                'has_blockchain' => false,
            ],
            [
                'title'          => 'Mercado de San Pedro al amanecer',
                'artist_name'    => 'Antonio Olave Palomino',
                'price'          => 7600.00,
                'description'    => 'Escena costumbrista que documenta el comercio tradicional del Cusco a inicios del siglo XX. Oleo sobre tela con influencia de la pintura academica limena y la tradicion narrativa andina.',
                'image_url'      => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 92.30,
                'has_blockchain' => false,
            ],
            [
                'title'          => 'Inti Raymi, ofrenda solar',
                'artist_name'    => 'Mariano Fuentes Lira',
                'price'          => 31200.00,
                'description'    => 'Pieza monumental 180x240 cm que captura la ceremonia del Inti Raymi en la Fortaleza de Sacsayhuaman con simbolismo incaico y pinceladas neobarrocas. Presentada en la Bienal de Arte Andino 2019.',
                'image_url'      => 'https://images.unsplash.com/photo-1577720643272-265f09367456?w=1200',
                'model_3d_url'   => 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',
                'status'         => 'available',
                'match'          => 97.65,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Choquequirao en la bruma',
                'artist_name'    => 'Edilberto Mérida',
                'price'          => 14400.00,
                'description'    => 'Paisaje contemplativo del complejo arqueologico de Choquequirao visto desde el mirador de Capuliyoc. Oleo sobre tela de algodon andino con imprimatura ocre. Atmosfera niebla matinal capturada in situ.',
                'image_url'      => 'https://images.unsplash.com/photo-1526481280699-c40e3d68fdf2?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 95.20,
                'has_blockchain' => false,
            ],
            [
                'title'          => 'Tejedoras de Chinchero',
                'artist_name'    => 'Maximiliana Palomino',
                'price'          => 8900.00,
                'description'    => 'Retrato colectivo de las maestras tejedoras de Chinchero, custodias del telar de cintura ancestral. Tecnica acuarela sobre papel Fabriano 300g con trazos en tinta ferrogalica.',
                'image_url'      => 'https://images.unsplash.com/photo-1604079628040-94301bb21b91?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 93.55,
                'has_blockchain' => false,
            ],
            [
                'title'          => 'La Ultima Cena Andina',
                'artist_name'    => 'Marcos Zapata',
                'price'          => 45000.00,
                'description'    => 'Replica documentada y certificada de la celebre obra de la Catedral del Cusco que muestra la Ultima Cena con elementos gastronomicos andinos. Ejecutada con criterios de alta fidelidad cromatica y compositiva.',
                'image_url'      => 'https://images.unsplash.com/photo-1561214115-f2f134cc4912?w=1200',
                'model_3d_url'   => 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',
                'status'         => 'available',
                'match'          => 99.87,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Nevado Ausangate en verano',
                'artist_name'    => 'Victor Delfín Quiroga',
                'price'          => 16800.00,
                'description'    => 'Vista del Ausangate a 6384 metros durante el solsticio de diciembre. Pintura al oleo con espátula sobre tela gruesa. Paleta fria y luminosa que evoca la experiencia mistica del Qoyllur Riti.',
                'image_url'      => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 91.40,
                'has_blockchain' => false,
            ],
            [
                'title'          => 'Danzantes de Tijeras',
                'artist_name'    => 'Joaquín López Antay',
                'price'          => 11200.00,
                'description'    => 'Representacion de los danzantes de tijeras de Ayacucho en plena competencia ritual. Obra en acrilico sobre tela con superposicion de capas veladas. Patrimonio Cultural de la Nacion declarado por la UNESCO.',
                'image_url'      => 'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 96.88,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Quechua madre e hijo',
                'artist_name'    => 'Hilario Mendívil',
                'price'          => 19500.00,
                'description'    => 'Escultura en yeso de papa pintada al frio que representa la iconografia maternal andina. Serie Nacimiento del mundo, pieza central. Cuello elongado caracteristico de la escuela mendivil reconocida internacionalmente.',
                'image_url'      => 'https://images.unsplash.com/photo-1555685812-4b943f1cb0eb?w=1200',
                'model_3d_url'   => 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',
                'status'         => 'available',
                'match'          => 98.10,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Plaza Mayor del Cusco 1890',
                'artist_name'    => 'Juan de Espinosa Medrano',
                'price'          => 52000.00,
                'description'    => 'Obra atribuida documentalmente al periodo colonial tardio. Vista panoramica de la Plaza Mayor del Cusco con fuente central y calesas. Oleo sobre lienzo encolado en tabla de cedro andino.',
                'image_url'      => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 97.33,
                'has_blockchain' => true,
            ],
            [
                'title'          => 'Chakana cosmica',
                'artist_name'    => 'Maximiliana Palomino',
                'price'          => 6400.00,
                'description'    => 'Serie geometrica sobre la cruz andina como eje del universo. Tecnica mixta, oro fino y arcilla cocida sobre panel de madera. Pieza de coleccion de formato reducido 40x40 cm.',
                'image_url'      => 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=1200',
                'model_3d_url'   => null,
                'status'         => 'available',
                'match'          => 89.75,
                'has_blockchain' => false,
            ],
            [
                'title'          => 'Rio Urubamba en agosto',
                'artist_name'    => 'Victor Delfín Quiroga',
                'price'          => 13300.00,
                'description'    => 'Vista del Rio Urubamba a su paso por el Valle Sagrado en epoca de estiaje. Impresionismo andino con influencia de la escuela de Barbizon. Colores ocres y verdes que evocan la temporada seca del altiplano.',
                'image_url'      => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200',
                'model_3d_url'   => null,
                'status'         => 'sold',
                'match'          => 95.60,
                'has_blockchain' => true,
            ],
        ];

        foreach ($catalog as $data) {
            $artwork = Artwork::updateOrCreate(
                ['title' => $data['title']],
                [
                    'artist_name'  => $data['artist_name'],
                    'price'        => $data['price'],
                    'description'  => $data['description'],
                    'image_url'    => '/obras/'.Str::slug($data['title']).'.jpg',
                    'model_3d_url' => $data['model_3d_url'] ? '/3d/'.Str::slug($data['title']).'.glb' : null,
                    'status'       => $data['status'],
                ],
            );

            Certificate::updateOrCreate(
                ['artwork_id' => $artwork->id],
                [
                    'biometric_hash'   => hash('sha256', $artwork->title . '|' . $artwork->artist_name),
                    'match_percentage' => $data['match'],
                    'blockchain_tx_id' => $data['has_blockchain'] ? '0x' . bin2hex(random_bytes(32)) : null,
                    'contract_address' => $data['has_blockchain'] ? '0x' . bin2hex(random_bytes(20)) : null,
                ],
            );
        }
    }
}
