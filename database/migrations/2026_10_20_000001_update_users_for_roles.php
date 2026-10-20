<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles del plan (§5): admin, artista, comprador.
 * Se eliminan los roles de inversión y la wallet (§29.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')->whereNotIn('role', ['admin', 'artista'])->update(['role' => 'comprador']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('comprador')->change();
            $table->dropColumn('wallet_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wallet_address')->nullable();
            $table->string('role')->default('investor')->change();
            $table->dropColumn('is_active');
        });

        DB::table('users')->where('role', 'comprador')->update(['role' => 'investor']);
    }
};
