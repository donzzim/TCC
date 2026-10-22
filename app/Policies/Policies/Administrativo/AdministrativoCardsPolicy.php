<?php

namespace App\Policies\Administrativo;

use App\Models\Administrativo\Cards;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AdministrativoCardsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_administrativo::cards');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cards $cards): bool
    {
        return $user->can('view_administrativo::cards');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_administrativo::cards');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cards $cards): bool
    {
        return $user->can('update_administrativo::cards');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cards $cards): bool
    {
        return $user->can('delete_administrativo::cards');
    }

    /**
     * Determine whether the user can bulk delete.

     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_administrativo::cards');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Cards $cards): bool
    {
        return $user->can('force_delete_administrativo::cards');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDeleteAny(User $user, Cards $cards): bool
    {
        return $user->can('force_delete_any_administrativo::cards');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Cards $cards): bool
    {
        return $user->can('restore_administrativo::cards');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_administrativo::cards');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function replicate(User $user): bool
    {
        return $user->can('replicate_administrativo::cards');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_administrativo::cards');
    }
}
