<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('verification_token', 64)->nullable()->unique()->after('contract_address');
        });

        // genera token para certificados ya existentes
        \App\Models\Certificate::whereNull('verification_token')->each(function ($cert) {
            $cert->update(['verification_token' => Str::uuid()->toString()]);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('verification_token');
        });
    }
};
