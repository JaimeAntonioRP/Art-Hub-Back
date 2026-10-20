<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quita los campos simulados (huella "biométrica", % de coincidencia de IA y
 * transacción blockchain), que el plan excluye (§29.4, §29.5).
 * El certificado PDF con huella real se diseña en la Etapa 2 (§19–§22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['biometric_hash', 'match_percentage', 'blockchain_tx_id', 'contract_address']);
            $table->string('status', 20)->default('vigente')->after('verification_token'); // vigente, anulado, reemplazado
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('biometric_hash')->default('');
            $table->decimal('match_percentage', 5, 2)->default(0);
            $table->string('blockchain_tx_id')->nullable();
            $table->string('contract_address')->nullable();
        });
    }
};
