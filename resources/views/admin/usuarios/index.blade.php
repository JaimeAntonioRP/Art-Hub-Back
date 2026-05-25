@extends('admin.layouts.app')

@section('page-title', 'Usuarios')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Todos los usuarios ({{ $usuarios->total() }})</h2>
        <form method="GET" action="{{ route('admin.usuarios.index') }}">
            <div class="search-wrap">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar nombre o email…">
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Registrado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $user)
                <tr>
                    <td style="color:var(--muted);">{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td style="color:var(--muted);">{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge badge-yellow">Admin</span>
                        @elseif($user->role === 'collector')
                            <span class="badge badge-blue">Coleccionista</span>
                        @else
                            <span class="badge badge-gray">{{ $user->role ?? 'user' }}</span>
                        @endif
                    </td>
                    <td style="color:var(--muted); font-size:12px;">
                        {{ $user->created_at?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.usuarios.role', $user) }}">
                            @csrf @method('PATCH')
                            <div style="display:flex; gap:6px; align-items:center;">
                                <select name="role" style="max-width:130px; padding:5px 8px; font-size:12px;">
                                    @foreach(['user','collector','admin'] as $r)
                                        <option value="{{ $r }}" {{ ($user->role ?? 'user') === $r ? 'selected' : '' }}>
                                            {{ ucfirst($r) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-ghost btn-sm">Guardar</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty">
                        <div class="icon">◉</div>
                        <p>No hay usuarios registrados.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($usuarios->hasPages())
    <div class="pagination-wrap">{{ $usuarios->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
