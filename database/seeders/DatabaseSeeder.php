<?php

namespace Database\Seeders;

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
        $this->seedArtworks();
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
                'artist_name'    => 'Edilberto Merida',
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
                'artist_name'    => 'Hilario Mendivil',
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
                'artist_name'    => 'Edilberto Merida',
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
                'artist_name'    => 'Marcos Zapata (replica documentada)',
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
                'artist_name'    => 'Victor Delfin Quiroga',
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
                'artist_name'    => 'Joaquin Lopez Antay',
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
                'artist_name'    => 'Hilario Mendivil',
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
                'artist_name'    => 'Juan de Espinosa Medrano (atribuida)',
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
                'artist_name'    => 'Victor Delfin Quiroga',
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
                    'image_url'    => $data['image_url'],
                    'model_3d_url' => $data['model_3d_url'],
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
