{{-- Versiones de un documento del expediente (§23), de la más reciente a la primera. --}}
@php use Illuminate\Support\Number; @endphp

<ol style="list-style: none; margin: 0; padding: 0; display: grid; gap: 12px;">
    @foreach ($versions as $version)
        <li style="border: 1px solid rgb(229 231 235); border-radius: 10px; padding: 14px 16px;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <strong style="font-size: 14px;">Versión {{ $version->version }}</strong>
                    @if ($loop->first)
                        <x-filament::badge color="info" size="sm">Vigente</x-filament::badge>
                    @endif
                    @if ($version->verification_ok === true)
                        <x-filament::badge color="success" size="sm">Íntegro</x-filament::badge>
                    @elseif ($version->verification_ok === false)
                        <x-filament::badge color="danger" size="sm">Alterado</x-filament::badge>
                    @endif
                </div>
                <x-filament::link :href="route('documents.download', $version)" target="_blank" icon="heroicon-o-arrow-down-tray" size="sm">
                    Descargar
                </x-filament::link>
            </div>

            <p style="margin: 6px 0 0; font-size: 13px; color: rgb(75 85 99);">
                {{ $version->original_name }} · {{ Number::fileSize($version->size, precision: 1) }}
                · {{ $version->created_at->format('d/m/Y H:i') }}
                · {{ $version->uploader?->name ?? 'Sistema' }}
            </p>

            @if ($version->reason)
                <p style="margin: 6px 0 0; font-size: 13px;"><span style="color: rgb(75 85 99);">Motivo:</span> {{ $version->reason }}</p>
            @endif

            <p style="margin: 8px 0 0; font-size: 12px; color: rgb(75 85 99);">
                Huella {{ strtoupper($version->hash_algorithm) }}
                @if ($version->verified_at)
                    · comprobada el {{ $version->verified_at->format('d/m/Y H:i') }}
                @endif
            </p>
            <code style="display: block; margin-top: 2px; font-size: 12px; word-break: break-all; color: rgb(17 24 39);">{{ $version->sha256 }}</code>
        </li>
    @endforeach
</ol>
