<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Artwork;
use Illuminate\Database\Seeder;

/**
 * Obras de muestra de dominio público (database/seeders/data/obras-muestra.json).
 *
 * Los datos salen de Wikidata y las imágenes de Wikimedia Commons; lo que no estaba en
 * esas fuentes queda vacío (nada se inventa). Se exhiben «No a la venta»: no reciben
 * solicitudes, no se venden ni se certifican. Los artistas no llevan biografía.
 *
 *   php artisan db:seed --class=SampleArtworksSeeder
 */
class SampleArtworksSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/obras-muestra.json')), true, flags: JSON_THROW_ON_ERROR);

        $artists = [];
        foreach ($data['artistas'] as $a) {
            $artists[$a['nombre']] = Artist::firstOrCreate(['slug' => $a['slug']], [
                'name'       => $a['nombre'],
                'origin'     => $a['origen'],
                'birth_year' => $a['nacimiento'],
                'death_year' => $a['muerte'],
                'status'     => Artist::STATUS_APROBADO,
            ]);
        }

        foreach ($data['obras'] as $o) {
            $artist = $artists[$o['artista']];
            if ($artist->artworks()->where('title', $o['titulo'])->exists()) {
                continue; // ya cargada: no se pisa lo editado en el panel
            }

            Artwork::create([
                'artist_id'    => $artist->id,
                'title'        => $o['titulo'],
                'year'         => $o['anio'],
                'year_circa'   => $o['anio_aproximado'],
                'technique'    => $o['tecnica'],
                'support'      => $o['soporte'],
                'height_cm'    => $o['alto_cm'],
                'width_cm'     => $o['ancho_cm'],
                'image_url'    => $o['imagen'],
                'source'       => $o['fuente'],
                'source_url'   => $o['fuente_url'],
                'license'      => $o['licencia'],
                'is_sample'    => true,
                'availability' => 'no_venta',
                'show_price'   => false,
                'price'        => null,
                'status'       => Artwork::STATUS_PUBLICADO,
            ]);
        }
    }
}
