<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;

/** Quién gestiona cada obra en los paneles: el admin todas; el artista solo las suyas (§7). */
class ArtworkPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() && $user->is_active ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === User::ROLE_ARTISTA;
    }

    public function view(User $user, Artwork $artwork): bool
    {
        return $this->owns($user, $artwork);
    }

    /** Un perfil rechazado o desactivado no registra obras nuevas. */
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_ARTISTA
            && $user->artist
            && ! in_array($user->artist->status, [Artist::STATUS_RECHAZADO, Artist::STATUS_INACTIVO], true);
    }

    /** Mientras la obra está en revisión o publicada, la gestiona el equipo de ArtHub (§10). */
    public function update(User $user, Artwork $artwork): bool
    {
        return $this->owns($user, $artwork) && in_array($artwork->status, Artwork::ARTIST_EDITABLE, true);
    }

    /** El artista solo borra obras que aún no envió a revisión. */
    public function delete(User $user, Artwork $artwork): bool
    {
        return $this->owns($user, $artwork) && $artwork->status === Artwork::STATUS_BORRADOR;
    }

    private function owns(User $user, Artwork $artwork): bool
    {
        // Enteros: según el driver, el id leído de la base puede llegar como texto.
        return $user->artist !== null && (int) $artwork->artist_id === (int) $user->artist->id;
    }
}
