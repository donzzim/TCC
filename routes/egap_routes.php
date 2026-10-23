<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Egap\PrintsControllers\AgendamentoMateriaisPrintController;
use App\Http\Controllers\Egap\PrintsControllers\DepreciacaoPrintController;
use App\Http\Controllers\Egap\PrintsControllers\EntregaRecolhimentoPrintController;
use App\Http\Controllers\Egap\PrintsControllers\BaixaPrintController;
use App\Http\Controllers\Egap\PrintsControllers\BensLotePrintController;
use App\Http\Controllers\Egap\PrintsControllers\BensNoSetorPrintController;
use App\Http\Controllers\Egap\PrintsControllers\PedidosPrintController;
use App\Http\Controllers\Egap\PrintsControllers\TermoInventarioPrintController;
use App\Http\Controllers\Egap\PrintsControllers\TermosPrintController;
use App\Http\Controllers\Egap\MailControllers\TestEmailController;
use App\Http\Controllers\Egap\RelatoriosController\RelatorioController;
use App\Http\Middleware\ApplyActiveTemplate;
use App\Filament\Egap\Auth\EgapSession;

Route::prefix('egap')
    ->middleware('auth:egap')
    ->withoutMiddleware(ApplyActiveTemplate::class)
    ->group(function () {
        Route::get('/session', function () {
            abort_unless(app()->isLocal(), 404);

            dd([
                'na sessão' => session('egap_session'),
                'via métodos' => [
                    'egap_id' => EgapSession::resolverAtual(),
                    'setor' => EgapSession::setorAtual(),
                    'codigodauo' => EgapSession::unidadeAtual(),
                    'info' => EgapSession::info(),
                ],
            ]);
        })->name('egap.session');

        Route::get('/pedidos/agendamento-entrega-recolhimento/imprimir', [EntregaRecolhimentoPrintController::class, 'print'])
            ->name('pedidos.entrega-recolhimento.imprimir');

        Route::get('/pedidos/{record}/imprimir', [PedidosPrintController::class, 'show'])->name('impressao_pedido');

        Route::controller(RelatorioController::class)->group(function () {
            Route::get('/relatorios-gerais/imprimir', 'imprimir')->name('relatorios.gerais.imprimir');
        });

        Route::group(['prefix' => 'agendamento'], function () {
            Route::get('/transporte/{id}/materiais/imprimir', [AgendamentoMateriaisPrintController::class, 'print'])
                ->name('agendamento.materiais.imprimir');
        });

        Route::group(['prefix' => 'patrimonio'], function () {
            Route::get('/bens-no-setor/imprimir', [BensNoSetorPrintController::class, 'print'])
                ->name('patrimonio.bens-no-setor.imprimir');

            Route::get('/baixa/{id}/imprimir', [BaixaPrintController::class, 'print'])
                ->name('termo.baixa.imprimir');

            Route::get('/depreciacao/{id}/imprimir', [DepreciacaoPrintController::class, 'print'])
                ->name('depreciacao.imprimir');

            Route::get('/bens-moveis/termos/{id}/imprimir', [TermosPrintController::class, 'print'])
                ->name('termo.imprimir');

            Route::get('/inventario/termos/{id}/imprimir', [TermoInventarioPrintController::class, 'print'])
                ->name('termo-inventario.imprimir');

            Route::get('/bens-selecionados/{ids}/imprimir', [BensLotePrintController::class, 'print'])
                ->where('ids', '[0-9]+(,[0-9]+)*')
                ->name('bens.imprimir.lote');
        });

        Route::controller(TestEmailController::class)->group(function () {
            Route::get('/test-email', 'sendEmail')->name('test-email');
        });
    }
);

