@extends('admin.layouts.app')

@section('page-title', isset($artista) ? 'Editar artista' : 'Nuevo artista')

@section('topbar-actions')
    <a href="{{ route('admin.artistas.index') }}" class="btn btn-ghost btn-sm">← Volver</a>
@endsection

@section('content')
<div class="form-card">
    <h2>{{ isset($artista) ? 'Editar artista' : 'Nuevo artista' }}</h2>

    @if($errors->any())
        <div class="flash error" style="margin-bottom:20px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST"
          action="{{ isset($artista) ? route('admin.artistas.update', $artista) : route('admin.artistas.store') }}">
        @csrf
        @if(isset($artista)) @method('PUT') @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="name" value="{{ old('name', $artista->name ?? '') }}" required>
                @error('name') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $artista->slug ?? '') }}"
                       placeholder="se genera automáticamente">
            </div>
            <div class="form-group">
                <label>Origen / Nacionalidad</label>
                <input type="text" name="origin" value="{{ old('origin', $artista->origin ?? '') }}">
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="specialty" value="{{ old('specialty', $artista->specialty ?? '') }}">
            </div>
            <div class="form-group">
                <label>Año nacimiento</label>
                <input type="number" name="birth_year" min="1800" max="2100"
                       value="{{ old('birth_year', $artista->birth_year ?? '') }}">
            </div>
            <div class="form-group">
                <label>Año fallecimiento (opcional)</label>
                <input type="number" name="death_year" min="1800" max="2100"
                       value="{{ old('death_year', $artista->death_year ?? '') }}">
            </div>
            <div class="form-group">
                <label>URL foto de perfil</label>
                <input type="url" name="profile_image_url"
                       value="{{ old('profile_image_url', $artista->profile_image_url ?? '') }}">
            </div>
            <div class="form-group" style="justify-content:flex-end; padding-top:22px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="featured" value="1" style="width:auto;"
                           {{ old('featured', $artista->featured ?? false) ? 'checked' : '' }}>
                    Artista destacado
                </label>
            </div>
            <div class="form-group full">
                <label>Biografía</label>
                <textarea name="bio" style="min-height:120px;">{{ old('bio', $artista->bio ?? '') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                {{ isset($artista) ? 'Guardar cambios' : 'Crear artista' }}
            </button>
            <a href="{{ route('admin.artistas.index') }}" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
@endsection
