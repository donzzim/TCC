<?php

namespace App\Policies\Administrativo;

use App\Models\User;
use App\Models\Administrativo\Cards;
use Illuminate\Auth\Access\HandlesAuthorization;

class CardsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_administrativo::card');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cards $cards): bool
    {
        return $user->can('view_administrativo::card');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_administrativo::card');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cards $cards): bool
    {
        return $user->can('update_administrativo::card');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cards $cards): bool
    {
        return $user->can('delete_administrativo::card');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_administrativo::card');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Cards $cards): bool
    {
        return $user->can('force_delete_administrativo::card');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_administrativo::card');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Cards $cards): bool
    {
        return $user->can('restore_administrativo::card');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_administrativo::card');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Cards $cards): bool
    {
        return $user->can('replicate_administrativo::card');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_administrativo::card');
    }
}
