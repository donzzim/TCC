<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->group(function (): void {
        Route::get('/user', function (Request $request) {
            return new UserResource($request->user());
        });

        Route::apiResource('users', UserController::class);
    });
