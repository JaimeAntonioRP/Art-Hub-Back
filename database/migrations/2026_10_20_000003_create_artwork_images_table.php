<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Galería de la obra (§8). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artwork_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('adicional'); // principal, adicional, posterior, detalle, firma
            $table->string('path', 2048);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['artwork_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_images');
    }
};
