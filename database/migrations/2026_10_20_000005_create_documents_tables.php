<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expediente documental (§9). Cada documento guarda sus archivos como versiones
 * desde el inicio: la Etapa 1 usa solo la versión 1 y la Etapa 2 (§23) agrega más.
 * El SHA-256 se calcula al subir, para que la Etapa 2 tenga la huella del original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30); // declaracion, certificado, procedencia, informe, peritaje, permiso, historico, otro
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('visibility', 10)->default('privado'); // privado, publico
            $table->boolean('is_relevant')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['artwork_id', 'category']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk', 20);
            $table->string('path', 2048);
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->text('reason')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'version']);
            $table->index('sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
