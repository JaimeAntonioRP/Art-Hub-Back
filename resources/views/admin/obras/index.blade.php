@extends('admin.layouts.app')

@section('page-title', 'Obras')

@section('topbar-actions')
    <a href="{{ route('admin.obras.create') }}" class="btn btn-primary btn-sm">+ Nueva obra</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Todas las obras ({{ $obras->total() }})</h2>
        <form method="GET" action="{{ route('admin.obras.index') }}">
            <div class="search-wrap">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar obra o artista…">
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Título</th>
                    <th>Artista</th>
                    <th>Técnica</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>3D</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($obras as $obra)
                <tr>
                    <td>
                        @if($obra->image_url)
                            <img src="{{ $obra->image_url }}" class="td-img" alt="">
                        @else
                            <div class="td-img"></div>
                        @endif
                    </td>
                    <td><strong>{{ $obra->title }}</strong></td>
                    <td>{{ $obra->artist_name }}</td>
                    <td>{{ $obra->technique ?? '—' }}</td>
                    <td>S/ {{ number_format($obra->price, 2) }}</td>
                    <td>
                        @if($obra->status === 'available')
                            <span class="badge badge-green">Disponible</span>
                        @elseif($obra->status === 'sold')
                            <span class="badge badge-red">Vendida</span>
                        @elseif($obra->status === 'reserved')
                            <span class="badge badge-yellow">Reservada</span>
                        @else
                            <span class="badge badge-gray">{{ $obra->status }}</span>
                        @endif
                    </td>
                    <td>
                        @if($obra->model_3d_url)
                            <span class="badge badge-blue">3D</span>
                        @else
                            <span style="color: var(--muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('admin.obras.edit', $obra) }}" class="btn btn-ghost btn-sm">Editar</a>
                            <form method="POST" action="{{ route('admin.obras.destroy', $obra) }}"
                                  onsubmit="return confirm('¿Eliminar esta obra?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8">
                    <div class="empty">
                        <div class="icon">🖼</div>
                        <p>No hay obras. <a href="{{ route('admin.obras.create') }}" style="color:var(--gold);">Crear la primera</a></p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($obras->hasPages())
    <div class="pagination-wrap">
        {{ $obras->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
