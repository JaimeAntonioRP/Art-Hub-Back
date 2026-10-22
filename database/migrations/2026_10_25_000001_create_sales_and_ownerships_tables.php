<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas (§26) y propietarios (§25). Todo se registra a mano: ArtHub no cobra en línea.
 * - sales: reserva, venta o transferencia; puede venir de una solicitud comercial.
 *   No se borra: un registro equivocado se anula con motivo y se deshacen sus efectos.
 * - ownerships: quién tuvo la obra y cuándo. El propietario actual es el de to_date vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commercial_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // reserva, venta, transferencia
            $table->date('date');
            $table->decimal('final_price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('party_name');            // comprador o nuevo propietario
            $table->string('party_document', 40)->nullable();
            $table->string('party_email')->nullable();
            $table->string('party_phone', 40)->nullable();
            $table->text('notes')->nullable();
            $table->string('availability_before', 20); // para deshacer el registro si se anula
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['artwork_id', 'type']);
        });

        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('owner_name');
            $table->string('owner_document', 40)->nullable();
            $table->string('owner_email')->nullable();
            $table->string('origin', 20); // artista (propietario inicial), venta, transferencia
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->timestamps();

            $table->index(['artwork_id', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownerships');
        Schema::dropIfExists('sales');
    }
};
