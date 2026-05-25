@extends('admin.layouts.app')

@section('page-title', 'Certificados')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Certificados emitidos ({{ $certificados->total() }})</h2>
        <form method="GET" action="{{ route('admin.certificados.index') }}">
            <div class="search-wrap">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por obra o token…">
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Obra</th>
                    <th>Token / Hash</th>
                    <th>Emitido</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificados as $cert)
                <tr>
                    <td style="color:var(--muted);font-family:monospace;">{{ $cert->id }}</td>
                    <td>{{ $cert->artwork->title ?? '—' }}</td>
                    <td>
                        <code style="font-size:11px; color:var(--muted); word-break:break-all;">
                            {{ Str::limit($cert->verification_token ?? $cert->biometric_hash ?? '—', 40) }}
                        </code>
                    </td>
                    <td style="color:var(--muted); font-size:12px;">
                        {{ $cert->created_at?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td>
                        @if(($cert->status ?? 'active') === 'active')
                            <span class="badge badge-green">Activo</span>
                        @else
                            <span class="badge badge-red">Revocado</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.certificados.toggle', $cert) }}"
                              onsubmit="return confirm('¿Cambiar estado del certificado?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-ghost btn-sm">
                                {{ ($cert->status ?? 'active') === 'active' ? 'Revocar' : 'Activar' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty">
                        <div class="icon">◎</div>
                        <p>No hay certificados emitidos.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($certificados->hasPages())
    <div class="pagination-wrap">{{ $certificados->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
