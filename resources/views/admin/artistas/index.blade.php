@extends('admin.layouts.app')

@section('page-title', 'Artistas')

@section('topbar-actions')
    <a href="{{ route('admin.artistas.create') }}" class="btn btn-primary btn-sm">+ Nuevo artista</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Todos los artistas ({{ $artistas->total() }})</h2>
        <form method="GET" action="{{ route('admin.artistas.index') }}">
            <div class="search-wrap">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar artista…">
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Nombre</th>
                    <th>Origen</th>
                    <th>Especialidad</th>
                    <th>Años</th>
                    <th>Destacado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($artistas as $artista)
                <tr>
                    <td>
                        @if($artista->profile_image_url)
                            <img src="{{ $artista->profile_image_url }}" class="td-img" alt=""
                                 style="border-radius:50%">
                        @else
                            <div class="td-img" style="border-radius:50%; display:flex;align-items:center;justify-content:center;font-size:18px;">
                                ✦
                            </div>
                        @endif
                    </td>
                    <td><strong>{{ $artista->name }}</strong><br><small style="color:var(--muted)">{{ $artista->slug }}</small></td>
                    <td>{{ $artista->origin ?? '—' }}</td>
                    <td>{{ $artista->specialty ?? '—' }}</td>
                    <td>{{ $artista->birth_year ?? '?' }}{{ $artista->death_year ? ' – '.$artista->death_year : '' }}</td>
                    <td>
                        @if($artista->featured)
                            <span class="badge badge-yellow">⭐ Sí</span>
                        @else
                            <span class="badge badge-gray">No</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="{{ route('admin.artistas.edit', $artista) }}" class="btn btn-ghost btn-sm">Editar</a>
                            <form method="POST" action="{{ route('admin.artistas.destroy', $artista) }}"
                                  onsubmit="return confirm('¿Eliminar este artista?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty">
                        <div class="icon">✦</div>
                        <p>No hay artistas. <a href="{{ route('admin.artistas.create') }}" style="color:var(--gold);">Añadir el primero</a></p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($artistas->hasPages())
    <div class="pagination-wrap">{{ $artistas->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
