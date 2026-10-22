<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Huellas digitales (§21) y comprobación de integridad (§22).
 * Cada versión de documento registra con qué algoritmo se calculó su huella y el
 * resultado de la última comprobación. Las imágenes subidas también guardan la suya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->string('hash_algorithm', 20)->default('sha256')->after('sha256');
            $table->timestamp('verified_at')->nullable()->after('hash_algorithm');
            $table->boolean('verification_ok')->nullable()->after('verified_at');
        });

        Schema::table('artwork_images', function (Blueprint $table) {
            $table->char('sha256', 64)->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropColumn(['hash_algorithm', 'verified_at', 'verification_ok']);
        });

        Schema::table('artwork_images', function (Blueprint $table) {
            $table->dropColumn('sha256');
        });
    }
};
