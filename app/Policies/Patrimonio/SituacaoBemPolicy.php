<?php

namespace App\Policies\Patrimonio;

use App\Models\User;
use App\Models\Patrimonio\SituacaoBem;
use Illuminate\Auth\Access\HandlesAuthorization;

class SituacaoBemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SituacaoBem $situacaoBem): bool
    {
        return $user->can('view_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SituacaoBem $situacaoBem): bool
    {
        return $user->can('update_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SituacaoBem $situacaoBem): bool
    {
        return $user->can('delete_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, SituacaoBem $situacaoBem): bool
    {
        return $user->can('force_delete_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, SituacaoBem $situacaoBem): bool
    {
        return $user->can('restore_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, SituacaoBem $situacaoBem): bool
    {
        return $user->can('replicate_patrimonio::situacao::bem');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_patrimonio::situacao::bem');
    }
}
