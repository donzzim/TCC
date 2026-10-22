<?php

namespace App\Policies\Cadastro;

use App\Models\Cadastro\Tabelas;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CadastroTabelasPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_cadastro::tabelas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tabelas $tabelas): bool
    {
        return $user->can('view_cadastro::tabelas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_cadastro::tabelas');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tabelas $tabelas): bool
    {
        return $user->can('update_cadastro::tabelas');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tabelas $tabelas): bool
    {
        return $user->can('delete_cadastro::tabelas');
    }

    /**
     * Determine whether the user can bulk delete.

     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_cadastro::tabelas');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tabelas $tabelas): bool
    {
        return $user->can('force_delete_cadastro::tabelas');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDeleteAny(User $user, Tabelas $tabelas): bool
    {
        return $user->can('force_delete_any_cadastro::tabelas');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tabelas $tabelas): bool
    {
        return $user->can('restore_cadastro::tabelas');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_cadastro::tabelas');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function replicate(User $user): bool
    {
        return $user->can('replicate_cadastro::tabelas');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_cadastro::tabelas');
    }
}
