<?php

namespace App\Policies\Escola;

use App\Models\Escola\ComunicacaoTipo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EscolaComunicacaoTipoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ComunicacaoTipo $comunicacaoTipo): bool
    {
        return $user->can('view_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ComunicacaoTipo $comunicacaoTipo): bool
    {
        return $user->can('update_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ComunicacaoTipo $comunicacaoTipo): bool
    {
        return $user->can('delete_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can bulk delete.

     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ComunicacaoTipo $comunicacaoTipo): bool
    {
        return $user->can('force_delete_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDeleteAny(User $user, ComunicacaoTipo $comunicacaoTipo): bool
    {
        return $user->can('force_delete_any_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ComunicacaoTipo $comunicacaoTipo): bool
    {
        return $user->can('restore_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function replicate(User $user): bool
    {
        return $user->can('replicate_escola::comunicacao::tipo');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_escola::comunicacao::tipo');
    }
}
