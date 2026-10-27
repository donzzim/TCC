<?php

use App\Http\Controllers\Egap\MobileApi\BensController;
use App\Http\Controllers\Egap\MobileApi\ConferenciaController;
use App\Http\Controllers\Egap\MobileApi\DashboardController;
use App\Http\Controllers\Egap\MobileApi\MobileAuthController;
use App\Http\Controllers\Egap\MobileApi\NotificacoesController;
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

            Route::prefix('notificacoes')->name('mobile.notificacoes.')->group(function () {
                Route::get('/', [NotificacoesController::class, 'index'])->name('index');
                Route::post('/lidas', [NotificacoesController::class, 'marcarTodasLidas'])->name('lidas');
                Route::post('/{id}/lida', [NotificacoesController::class, 'marcarLida'])->name('lida');
            });
        });

        Route::middleware(['mobile.sessao', 'mobile.setor'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'show'])->name('mobile.dashboard');
            Route::get('/bens', [BensController::class, 'index'])->name('mobile.bens');
            Route::get('/bens/{patrimonio}', [BensController::class, 'show'])->name('mobile.bens.show');

            Route::prefix('conferencia')->group(function () {
                Route::get('/', [ConferenciaController::class, 'atual'])->name('mobile.conferencia.atual');
                Route::get('/bens', [ConferenciaController::class, 'bens'])->name('mobile.conferencia.bens');
                Route::post('/leituras', [ConferenciaController::class, 'leituras'])->name('mobile.conferencia.leituras');
                Route::post('/nao-localizados', [ConferenciaController::class, 'naoLocalizados'])->name('mobile.conferencia.nao-localizados');
                Route::post('/divergencias', [ConferenciaController::class, 'divergencias'])->name('mobile.conferencia.divergencias');
                Route::post('/finalizar', [ConferenciaController::class, 'finalizar'])->name('mobile.conferencia.finalizar');
            });

            Route::prefix('pedidos')->name('mobile.pedidos.')->group(function () {
                Route::get('/', [PedidosController::class, 'index'])->name('index');
                Route::get('/contexto', [PedidosController::class, 'contexto'])->name('contexto');
                Route::get('/materiais', [PedidosController::class, 'materiais'])->name('materiais');
                Route::post('/', [PedidosController::class, 'store'])->name('store');
                Route::get('/{pedido}', [PedidosController::class, 'show'])->whereNumber('pedido')->name('show');
            });
        });
    });
});
