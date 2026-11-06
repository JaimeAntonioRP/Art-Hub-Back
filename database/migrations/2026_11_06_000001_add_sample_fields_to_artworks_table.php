<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obras de muestra de dominio público (La Gioconda, Noche estrellada…): se exhiben en el
 * catálogo «No a la venta», con fuente y licencia de la imagen en la ficha pública.
 * year_circa marca los años aproximados («c. 1830»), que la fuente da por década o siglo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->boolean('is_sample')->default(false)->index()->after('availability');
            $table->boolean('year_circa')->default(false)->after('year');
            $table->string('source', 120)->nullable()->after('history_notes');   // de dónde sale la imagen
            $table->string('source_url', 2048)->nullable()->after('source');
            $table->string('license', 120)->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropIndex(['is_sample']);
            $table->dropColumn(['is_sample', 'year_circa', 'source', 'source_url', 'license']);
        });
    }
};
