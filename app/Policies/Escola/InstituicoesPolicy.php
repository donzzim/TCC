<?php

namespace App\Policies\Escola;

use App\Models\User;
use App\Models\Escola\Instituicoes;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstituicoesPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_escola::instituicoes');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Instituicoes $instituicoes): bool
    {
        return $user->can('view_escola::instituicoes');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_escola::instituicoes');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Instituicoes $instituicoes): bool
    {
        return $user->can('update_escola::instituicoes');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Instituicoes $instituicoes): bool
    {
        return $user->can('delete_escola::instituicoes');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_escola::instituicoes');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Instituicoes $instituicoes): bool
    {
        return $user->can('force_delete_escola::instituicoes');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_escola::instituicoes');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Instituicoes $instituicoes): bool
    {
        return $user->can('restore_escola::instituicoes');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_escola::instituicoes');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Instituicoes $instituicoes): bool
    {
        return $user->can('replicate_escola::instituicoes');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_escola::instituicoes');
    }
}
