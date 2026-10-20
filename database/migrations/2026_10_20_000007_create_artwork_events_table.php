<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historial operativo de la obra (§11). La Etapa 2 (§24) solo agrega tipos de evento. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artwork_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['artwork_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_events');
    }
};
