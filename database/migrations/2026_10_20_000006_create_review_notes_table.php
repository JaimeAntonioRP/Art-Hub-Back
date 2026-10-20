<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Observaciones del administrador sobre una obra o un perfil de artista (§6, §10). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable'); // Artwork o Artist
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_notes');
    }
};
