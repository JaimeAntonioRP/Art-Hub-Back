<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Certificado digital de la obra (§19–§20).
 * - number: número legible (código de la obra + correlativo de emisión).
 * - snapshot + data_sha256: los datos certificados y su huella; esta huella va impresa en el PDF.
 * - pdf_sha256: huella del PDF emitido; se publica en el portal (un PDF no puede contener su propia huella).
 * - Estados: vigente, anulado (con motivo) y reemplazado (por una reemisión).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('number', 40)->nullable()->unique()->after('artwork_id');
            $table->json('snapshot')->nullable()->after('status');
            $table->char('data_sha256', 64)->nullable()->after('snapshot');
            $table->string('pdf_disk', 20)->nullable()->after('data_sha256');
            $table->string('pdf_path', 2048)->nullable()->after('pdf_disk');
            $table->char('pdf_sha256', 64)->nullable()->index()->after('pdf_path');
            $table->timestamp('issued_at')->nullable()->after('pdf_sha256');
            $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('issued_by');
            $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users')->nullOnDelete();
            $table->text('revoke_reason')->nullable()->after('revoked_by');
            $table->foreignId('replaced_by_id')->nullable()->after('revoke_reason')->constrained('certificates')->nullOnDelete();
        });

        // Los certificados de la versión anterior no tienen PDF ni huellas: quedan anulados con su motivo.
        DB::table('certificates')->whereNull('number')->update([
            'status' => 'anulado',
            'revoked_at' => now(),
            'revoke_reason' => 'Registro de la versión anterior de la plataforma, sin documento ni huella digital.',
        ]);
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by_id');
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropConstrainedForeignId('issued_by');
            $table->dropUnique(['number']);
            $table->dropIndex(['pdf_sha256']);
            $table->dropColumn(['number', 'snapshot', 'data_sha256', 'pdf_disk', 'pdf_path', 'pdf_sha256', 'issued_at', 'revoked_at', 'revoke_reason']);
        });
    }
};
