<?php

use App\Http\Controllers\Egap\MobileApi\BensController;
use App\Http\Controllers\Egap\MobileApi\ConferenciaBensController;
use App\Http\Controllers\Egap\MobileApi\MobileAuthController;
use App\Http\Controllers\Egap\MobileApi\PedidosController;
use App\Http\Controllers\Egap\MobileApi\SessaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile-api')->group(function (): void {
    Route::post('/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:mobile-login')
        ->name('mobile-login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('mobile.sessao')->group(function () {
            Route::get('/sessao', [SessaoController::class, 'show'])->name('mobile.sessao');
            Route::put('/sessao/setor', [SessaoController::class, 'trocarSetor'])->name('mobile.sessao.setor');
            Route::post('/logout', [SessaoController::class, 'logout'])->name('mobile.logout');
        });

        Route::get('/dashboard', [BensController::class, 'dashboard'])->name('mobile.dashboard');
        Route::get('/bens', [BensController::class, 'index'])->name('mobile.bens');
        Route::get('/bens/{numPatrimonio}', [BensController::class, 'show'])->name('mobile.bens.show');

        Route::prefix('pedidos')->name('mobile.pedidos.')->group(function () {
            Route::get('/', [PedidosController::class, 'index'])->name('index');
            Route::get('/contexto', [PedidosController::class, 'contexto'])->name('contexto');
            Route::get('/materiais', [PedidosController::class, 'materiais'])->name('materiais');
            Route::post('/', [PedidosController::class, 'store'])->name('store');
        });

        Route::prefix('conferencia')->name('mobile.conferencia.')->group(function () {
            Route::get('/atual', [ConferenciaBensController::class, 'atual'])->name('atual');
            Route::get('/bens', [ConferenciaBensController::class, 'bens'])->name('bens');
            Route::post('/validar-leitura', [ConferenciaBensController::class, 'validarLeitura'])->name('validar-leitura');
            Route::post('/localizar', [ConferenciaBensController::class, 'localizar'])->name('localizar');
            Route::post('/nao-localizados', [ConferenciaBensController::class, 'naoLocalizados'])->name('nao-localizados');
            Route::post('/divergencias', [ConferenciaBensController::class, 'divergencias'])->name('divergencias');
            Route::post('/finalizar', [ConferenciaBensController::class, 'finalizar'])->name('finalizar');
        });
    });
});
