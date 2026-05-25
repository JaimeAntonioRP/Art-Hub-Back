@extends('admin.layouts.app')

@section('page-title', isset($obra) ? 'Editar obra' : 'Nueva obra')

@section('topbar-actions')
    <a href="{{ route('admin.obras.index') }}" class="btn btn-ghost btn-sm">← Volver</a>
@endsection

@section('content')
<div class="form-card">
    <h2>{{ isset($obra) ? 'Editar obra' : 'Nueva obra' }}</h2>

    @if($errors->any())
        <div class="flash error" style="margin-bottom:20px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST"
          action="{{ isset($obra) ? route('admin.obras.update', $obra) : route('admin.obras.store') }}">
        @csrf
        @if(isset($obra)) @method('PUT') @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Título *</label>
                <input type="text" name="title" value="{{ old('title', $obra->title ?? '') }}" required>
                @error('title') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Artista *</label>
                <input type="text" name="artist_name" value="{{ old('artist_name', $obra->artist_name ?? '') }}" required list="artistas-list">
                <datalist id="artistas-list">
                    @foreach($artistas as $a)
                        <option value="{{ $a->name }}">
                    @endforeach
                </datalist>
            </div>
            <div class="form-group">
                <label>Precio (S/)</label>
                <input type="number" name="price" step="0.01" min="0"
                       value="{{ old('price', $obra->price ?? '') }}">
            </div>
            <div class="form-group">
                <label>Año</label>
                <input type="number" name="year" min="1800" max="2100"
                       value="{{ old('year', $obra->year ?? '') }}">
            </div>
            <div class="form-group">
                <label>Técnica</label>
                <input type="text" name="technique" value="{{ old('technique', $obra->technique ?? '') }}">
            </div>
            <div class="form-group">
                <label>Dimensiones</label>
                <input type="text" name="dimensions" placeholder="ej. 60x90 cm"
                       value="{{ old('dimensions', $obra->dimensions ?? '') }}">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    @foreach(['available','sold','reserved','hidden'] as $s)
                        <option value="{{ $s }}" {{ old('status', $obra->status ?? 'available') === $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>URL imagen</label>
                <input type="url" name="image_url" value="{{ old('image_url', $obra->image_url ?? '') }}">
            </div>
            <div class="form-group">
                <label>URL modelo 3D</label>
                <input type="url" name="model_3d_url" value="{{ old('model_3d_url', $obra->model_3d_url ?? '') }}">
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <textarea name="description">{{ old('description', $obra->description ?? '') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                {{ isset($obra) ? 'Guardar cambios' : 'Crear obra' }}
            </button>
            <a href="{{ route('admin.obras.index') }}" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
@endsection
