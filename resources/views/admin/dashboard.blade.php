@extends('admin.layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Obras</div>
        <div class="value">{{ $stats['obras'] }}</div>
        <div class="sub">en catálogo</div>
    </div>
    <div class="stat-card">
        <div class="label">Artistas</div>
        <div class="value">{{ $stats['artistas'] }}</div>
        <div class="sub">registrados</div>
    </div>
    <div class="stat-card">
        <div class="label">Certificados</div>
        <div class="value">{{ $stats['certificados'] }}</div>
        <div class="sub">emitidos</div>
    </div>
    <div class="stat-card">
        <div class="label">Usuarios</div>
        <div class="value">{{ $stats['usuarios'] }}</div>
        <div class="sub">registrados</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Obras recientes</h2>
        <a href="{{ route('admin.obras.index') }}" class="btn btn-ghost btn-sm">Ver todas</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Título</th>
                    <th>Artista</th>
                    <th>Precio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentArtworks as $obra)
                <tr>
                    <td>
                        @if($obra->image_url)
                            <img src="{{ $obra->image_url }}" class="td-img" alt="">
                        @else
                            <div class="td-img"></div>
                        @endif
                    </td>
                    <td>{{ $obra->title }}</td>
                    <td>{{ $obra->artist_name }}</td>
                    <td>S/ {{ number_format($obra->price, 2) }}</td>
                    <td>
                        @if($obra->status === 'available')
                            <span class="badge badge-green">Disponible</span>
                        @elseif($obra->status === 'sold')
                            <span class="badge badge-red">Vendida</span>
                        @else
                            <span class="badge badge-gray">{{ $obra->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty"><p>Sin obras registradas</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
