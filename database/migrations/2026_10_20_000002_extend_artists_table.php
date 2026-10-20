<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil de artista (§6): ligado a un usuario, con estado de revisión.
 * Los artistas existentes quedan aprobados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
            $table->string('artistic_name')->nullable()->after('name');
            $table->json('techniques')->nullable()->after('specialty');
            $table->text('career')->nullable()->after('bio');
            $table->text('awards')->nullable()->after('career');
            $table->string('contact_email')->nullable()->after('awards');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
            $table->json('social_links')->nullable()->after('contact_phone');
            $table->string('status', 20)->default('pendiente')->index()->after('featured');

            // Un artista recién registrado completa su perfil después.
            $table->string('specialty')->nullable()->change();
            $table->text('bio')->nullable()->change();
            $table->string('profile_image_url')->nullable()->change();
        });

        DB::table('artists')->update(['status' => 'aprobado']);
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'user_id', 'artistic_name', 'techniques', 'career', 'awards',
                'contact_email', 'contact_phone', 'social_links', 'status',
            ]);
        });
    }
};
