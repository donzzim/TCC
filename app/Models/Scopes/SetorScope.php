<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SetorScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            if (auth()->user()->hasRole('super_admin')) {
                return;
            }

            if (Session::has('setorprincipal')) {
                $setorId = Session::get('setorprincipal');

                $builder->where($model->getTable() . '.setor_id', $setorId);
            }
        }
    }
}
