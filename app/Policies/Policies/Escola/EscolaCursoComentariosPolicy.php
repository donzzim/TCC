<?php

namespace App\Policies\Escola;

use App\Models\Escola\CursoComentarios;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EscolaCursoComentariosPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_anyescola::curso::comentarios');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CursoComentarios $cursoComentarios): bool
    {
        return $user->can('viewescola::curso::comentarios');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('createescola::curso::comentarios');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CursoComentarios $cursoComentarios): bool
    {
        return $user->can('updateescola::curso::comentarios');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CursoComentarios $cursoComentarios): bool
    {
        return $user->can('deleteescola::curso::comentarios');
    }

    /**
     * Determine whether the user can bulk delete.

     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_anyescola::curso::comentarios');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CursoComentarios $cursoComentarios): bool
    {
        return $user->can('force_deleteescola::curso::comentarios');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDeleteAny(User $user, CursoComentarios $cursoComentarios): bool
    {
        return $user->can('force_delete_anyescola::curso::comentarios');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CursoComentarios $cursoComentarios): bool
    {
        return $user->can('restoreescola::curso::comentarios');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_anyescola::curso::comentarios');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function replicate(User $user): bool
    {
        return $user->can('replicateescola::curso::comentarios');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorderescola::curso::comentarios');
    }
}
