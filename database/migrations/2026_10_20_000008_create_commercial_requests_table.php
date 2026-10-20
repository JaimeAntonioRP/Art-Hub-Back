<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Solicitudes de información o intención de compra (§14). El cierre es externo a ArtHub. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('type', 20)->default('informacion'); // informacion, intencion_compra
            $table->text('message')->nullable();
            $table->string('status', 20)->default('nueva')->index(); // nueva, contactado, en_evaluacion, confirmada, cancelada
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_requests');
    }
};
