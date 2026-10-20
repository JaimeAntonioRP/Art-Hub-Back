<?php

use App\Models\Artwork;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ficha de obra del plan (§4, §7, §10):
 * - la obra se une al artista por artist_id (antes por artist_name en texto);
 * - medidas numéricas en cm (la AR de la Etapa 2 las necesita);
 * - estado editorial (status) separado de la disponibilidad comercial (availability);
 * - código único AH-XXX-AAAA-NNNNNN;
 * - la imagen pasa a la tabla artwork_images.
 *
 * Los datos existentes se transforman, no se borran.
 */
return new class extends Migration
{
    /** status antiguo => [status editorial, disponibilidad] */
    private const LEGACY_STATUS = [
        'available' => ['publicado', 'disponible'],
        'sold'      => ['publicado', 'vendida'],
        'reserved'  => ['publicado', 'reservada'],
        'hidden'    => ['despublicado', 'disponible'],
    ];

    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->unique()->after('id');
            $table->foreignId('artist_id')->nullable()->after('code')->constrained()->restrictOnDelete();
            $table->string('support')->nullable()->after('technique');
            $table->decimal('height_cm', 8, 2)->nullable()->after('support');
            $table->decimal('width_cm', 8, 2)->nullable()->after('height_cm');
            $table->decimal('depth_cm', 8, 2)->nullable()->after('width_cm');
            $table->decimal('weight_kg', 8, 2)->nullable()->after('depth_cm');
            $table->string('currency', 3)->default('USD')->after('price');
            $table->boolean('show_price')->default(true)->after('currency');
            $table->string('availability', 20)->default('disponible')->index()->after('status');
            $table->string('location')->nullable()->after('availability');
            $table->text('history_notes')->nullable()->after('location');
            $table->timestamp('published_at')->nullable()->after('history_notes');
        });

        DB::table('artworks')->lazyById()->each(function (object $row) {
            $artist = $this->findOrCreateArtist($row->artist_name);
            [$status, $availability] = self::LEGACY_STATUS[$row->status] ?? ['borrador', 'disponible'];
            $dims = Artwork::parseDimensions($row->dimensions);

            DB::table('artworks')->where('id', $row->id)->update([
                'artist_id'    => $artist->id,
                'code'         => Artwork::buildCode($artist->origin, $row->created_at, $row->id),
                'status'       => $status,
                'availability' => $availability,
                'published_at' => $status === 'publicado' ? $row->updated_at : null,
                'height_cm'    => $dims['height_cm'],
                'width_cm'     => $dims['width_cm'],
                'depth_cm'     => $dims['depth_cm'],
            ]);

            if ($row->image_url) {
                DB::table('artwork_images')->insert([
                    'artwork_id' => $row->id,
                    'kind'       => 'principal',
                    'path'       => $row->image_url,
                    'position'   => 0,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::table('artworks', function (Blueprint $table) {
            $table->foreignId('artist_id')->nullable(false)->change();
            $table->string('status', 20)->default('borrador')->index()->change();
            $table->decimal('price', 12, 2)->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->dropColumn(['artist_name', 'image_url', 'dimensions']);
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->string('artist_name')->nullable();
            $table->string('image_url')->nullable();
            $table->string('dimensions')->nullable();
        });

        $legacy = [];
        foreach (self::LEGACY_STATUS as $old => [$status, $availability]) {
            $legacy[$status][$availability] ??= $old;
        }

        DB::table('artworks')->lazyById()->each(function (object $row) use ($legacy) {
            DB::table('artworks')->where('id', $row->id)->update([
                'artist_name' => DB::table('artists')->where('id', $row->artist_id)->value('name'),
                'image_url'   => DB::table('artwork_images')->where('artwork_id', $row->id)
                    ->orderByDesc('is_primary')->orderBy('position')->value('path'),
                'dimensions'  => Artwork::formatDimensions($row->height_cm, $row->width_cm, $row->depth_cm),
                'status'      => $legacy[$row->status][$row->availability] ?? 'hidden',
            ]);
        });

        Schema::table('artworks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('artist_id');
            $table->dropUnique(['code']);
            $table->dropIndex(['availability']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'code', 'support', 'height_cm', 'width_cm', 'depth_cm', 'weight_kg',
                'currency', 'show_price', 'availability', 'location', 'history_notes', 'published_at',
            ]);
            $table->string('status')->default('available')->change();
        });
    }

    /** Obras cuyo artista solo existía como texto: se crea su perfil para no perder la relación. */
    private function findOrCreateArtist(?string $name): object
    {
        $name = trim((string) $name) ?: 'Artista sin nombre';

        $artist = DB::table('artists')->where('name', $name)->first();
        if ($artist) {
            return $artist;
        }

        $slug = $base = Str::slug($name) ?: 'artista';
        for ($i = 2; DB::table('artists')->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        $id = DB::table('artists')->insertGetId([
            'name'       => $name,
            'slug'       => $slug,
            'origin'     => 'Cusco, Perú',
            'status'     => 'aprobado',
            'featured'   => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('artists')->find($id);
    }
};
