<?php


use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\PortalAssistantController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\ReportProjetoPedagogicoController;
use App\Http\Controllers\AutoReportController;
use App\Livewire\CancelarInscricao;
use App\Livewire\Frequencia;
use App\Livewire\Inscricao;
use App\Livewire\LevantamentoMagistrados;
use App\Livewire\LevantamentoServidores;
use App\Mail\InscricaoMail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

require __DIR__ . '/egap_routes.php';


#Route::get('/', function () {
#    return redirect('/emes');
#});


#Route::group(['prefix' => 'emes'], function () {

Route::get('/portal', [PortalController::class, 'index'])->name('portal');
Route::get('/solicitacao/{id}', [PrincipalController::class, 'imprimirSolicitacao'])->name('imprimir.solicitacao');
Route::get('/certificado/{uuid}', [PrincipalController::class, 'imprimirCertificado'])->name('imprimir.certificado');
Route::get('/certificado/residente/{id}', [PrincipalController::class, 'imprimirCertificadoResidente'])->name('imprimir.certificado.residente');
Route::get('/comunicacao/{uuid}', [PrincipalController::class, 'imprimirComunicacao'])->name('imprimir.comunicacao');
Route::get('/inscricao/{uuid}', Inscricao::class)->name('realizar.inscricao');
Route::get('/levantamento-servidores', LevantamentoServidores::class)->name('realizar.levantamento.servidores');
Route::get('/levantamento-magistrados', LevantamentoMagistrados::class)->name('realizar.levantamento.magistrados');
Route::get('/cancelar/{uuid}', CancelarInscricao::class)->name('cancelar.inscricao');
Route::get('/frequencia/{uuid}', Frequencia::class)->name('realizar.frequencia');
Route::get('/frequencia/{uuid}/qrcode', [PrincipalController::class, 'qrcodeFrequencia'])->name('qrcode.frequencia');
Route::get('/forms/{slug}/{uuid}', [PublicFormController::class, 'show'])->name('forms.show');
Route::post('/forms/{slug}', [PublicFormController::class, 'submit'])->name('forms.submit');
Route::get('/curso/{uuid}/cartaz-qrcode-frequencia', [PrincipalController::class, 'cartazQrcodeFrequencia'])
    ->name('imprimir.cartaz-qrcode-frequencia');

// Novas Rotas do CMS (Adicionadas ao Final do Grupo)
Route::get('/', [PortalController::class, 'index'])->name('portal.home');
Route::get('/pesquisa', [PortalController::class, 'search'])->name('portal.search');
Route::get('/noticias', [PortalController::class, 'showAllPosts'])->name('portal.posts.index');
Route::get('/certificados', [PortalController::class, 'showAllCertificados'])->name('portal.certificados');
Route::post('/portal/assistente', PortalAssistantController::class)
    ->middleware('throttle:30,1')
    ->name('portal.assistente.search');
Route::get('/course/{uuid}', [PortalController::class, 'detalheCurso'])->name('portal.course');
Route::get('/courses', [PortalController::class, 'showAllCourses'])->name('portal.courses');
Route::get('/noticias/{slug}', [PortalController::class, 'showPost'])->name('portal.post');

Route::get('/egap-mobile/{path?}', function (?string $path = null) {
    $path = $path ? trim($path, '/') : 'index';
    $file = public_path("egap-mobile/{$path}.html");

    if (File::exists($file)) {
        return response()->file($file);
    }

    return response()->file(public_path('egap-mobile/index.html'));
})->where('path', '.*')->name('egap-mobile.pwa');

// endereço antigo do app web (favoritos e atalhos instalados)
Route::get('/inventario/{path?}', fn(?string $path = null) => redirect()->to(
    '/egap-mobile'.($path ? '/'.trim($path, '/') : ''),
    301,
))->where('path', '.*');

Route::get('/{slug}', [PortalController::class, 'showPage'])->name('portal.page');

// reports
Route::post('/relatorios/projeto-pedagogico', [ReportProjetoPedagogicoController::class, 'generate'])
    ->name('reports.pedagogico.generate')
    ->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/relatorios/automaticos/{curso}/{batch}', [AutoReportController::class, 'show'])
        ->name('reports.automaticos.show');
    Route::get('/relatorios/automaticos/{curso}/{batch}/download/{report}', [AutoReportController::class, 'download'])
        ->name('reports.automaticos.download');
});
#});

$appBasePath = trim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
$livewirePrefix = $appBasePath === '' ? '' : '/' . $appBasePath;
$livewireRoutePrefix = $appBasePath === ''
    ? ''
    : str_replace('/', '.', $appBasePath) . '.';

Livewire::setUpdateRoute(function ($handle) use ($livewirePrefix, $livewireRoutePrefix) {
    return Route::post($livewirePrefix . '/livewire/update', $handle)
        ->name($livewireRoutePrefix . 'livewire.update');
});

Livewire::setScriptRoute(function ($handle) use ($livewirePrefix, $livewireRoutePrefix) {
    return Route::get($livewirePrefix . '/livewire/livewire.js', $handle)
        ->name($livewireRoutePrefix . 'livewire');
});
