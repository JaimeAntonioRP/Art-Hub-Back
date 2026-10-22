<?php

namespace App\Console\Commands;

use App\Models\DocumentVersion;
use Illuminate\Console\Command;

/**
 * Comprueba la integridad de todos los archivos del expediente (§22).
 * Pensado para programarse a diario en el servidor; cada fallo queda en el historial de su obra.
 */
class VerifyIntegrity extends Command
{
    protected $signature = 'arthub:comprobar-integridad {--obra= : Solo los documentos de esta obra (id)}';

    protected $description = 'Vuelve a calcular la huella de cada archivo del expediente y la compara con la registrada';

    public function handle(): int
    {
        $query = DocumentVersion::with('document.artwork:id,code,title')
            ->when($this->option('obra'), fn ($q, $id) => $q->whereHas('document', fn ($d) => $d->where('artwork_id', $id)));

        $checked = 0;
        $failed = [];

        $query->lazyById()->each(function (DocumentVersion $version) use (&$checked, &$failed) {
            $checked++;
            if (! $version->verifyIntegrity()) {
                $failed[] = [$version->document->artwork?->code, $version->document->title, $version->version];
            }
        });

        if ($failed) {
            $this->error(count($failed)." de {$checked} archivos no coinciden con su huella:");
            $this->table(['Obra', 'Documento', 'Versión'], $failed);

            return self::FAILURE;
        }

        $this->info("Integridad verificada: {$checked} archivos coinciden con su huella.");

        return self::SUCCESS;
    }
}
