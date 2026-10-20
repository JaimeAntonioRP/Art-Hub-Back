<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** La procedencia la completa el artista en su perfil; al registrarse aún no se conoce. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->string('origin')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('artists')->whereNull('origin')->update(['origin' => 'Cusco, Peru']);

        Schema::table('artists', function (Blueprint $table) {
            $table->string('origin')->default('Cusco, Peru')->change();
        });
    }
};
