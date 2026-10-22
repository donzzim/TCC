<?php

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\Access\AuthorizationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')->group(base_path('routes/egap_pwa.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\LogRequestTiming::class);
        $middleware->web(append: [
            \App\Http\Middleware\ApplyActiveTemplate::class,
            // \App\Http\Middleware\AdminDebugbarMiddleware::class, // <-- Middleware para ativar o Debugbar apenas para admins
        ]);
        $middleware->redirectGuestsTo(fn() => route('filament.egap.auth.login'));
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withProviders([
        App\Providers\SettingsServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // 403 dentro do painel EGAP: mostra a página de acesso negado com a marca
        // do EGAP em vez do erro genérico. Livewire (SPA) cai fora daqui de
        // propósito — ao receber o 403 ele faz um reload completo, que então
        // entra por este mesmo callback via GET e renderiza a view.
        $exceptions->render(function (Throwable $e, $request) {
            $isForbidden = $e instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403);

            if (
                $isForbidden
                && $request->is('egap', 'egap/*')
                && ! $request->hasHeader('X-Livewire')
                && ! $request->expectsJson()
            ) {
                return response()->view('egap.403-page', [], 403);
            }
        });

        // 404 dentro do painel EGAP: mostra a página "não encontrado" com a
        // marca do EGAP em vez do erro genérico do Laravel. Mesma lógica do
        // 403 acima — Livewire e requisições JSON caem fora de propósito.
        $exceptions->render(function (Throwable $e, $request) {
            $isNotFound = $e instanceof NotFoundHttpException
                || $e instanceof ModelNotFoundException;

            if (
                $isNotFound
                && $request->is('egap', 'egap/*')
                && ! $request->hasHeader('X-Livewire')
                && ! $request->expectsJson()
            ) {
                return response()->view('egap.404-page', [], 404);
            }
        });

        // 419 dentro do painel EGAP (token CSRF expirado, ex.: "Sair" numa aba
        // antiga). O Laravel já converteu o TokenMismatchException em
        // HttpException 419 antes deste callback. Livewire trata o 419 sozinho.
        $exceptions->render(function (Throwable $e, $request) {
            if (
                $e instanceof HttpExceptionInterface
                && $e->getStatusCode() === 419
                && $request->is('egap', 'egap/*')
                && ! $request->hasHeader('X-Livewire')
                && ! $request->expectsJson()
            ) {
                return response()->view('egap.419-page', [], 419);
            }
        });

        $exceptions->renderable(function (Throwable $e, $request) {
            // dd('Exceção capturada globalmente: ' . $e->getMessage());
            // if (
            //     $e instanceof BindingResolutionException ||
            //     $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ||
            //     $e instanceof \Illuminate\Auth\AuthenticationException
            // ) {
            //     return;
            // }

            // --- BLOCO DE IGNORAR ERROS 404 (ADICIONADO) ---
            // Se for erro de rota (404) ou Model não encontrado, retorna imediatamente.
            // O Laravel vai exibir a página 404 padrão e não vai gerar log de erro ou notificação.
            if (
                $e instanceof NotFoundHttpException ||
                str_contains($e->getMessage(), 'O campo') ||
                $e instanceof ModelNotFoundException ||
                str_contains($e->getMessage(), 'The route') ||
                str_contains($e->getMessage(), 'Route [login]')
            ) {
                return;
            }

            // Acesso negado (403) é regra de permissão, não erro de aplicação:
            // não gera log nem notificação para os admins.
            if (
                $e instanceof AuthorizationException ||
                ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403)
            ) {
                return;
            }

            // Ignora erros de autenticação (redireciona para login normalmente)
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return;
            }

            // Cuidado com este bloco abaixo que você já tinha.
            // Ele pode ocultar erros reais do framework que você gostaria de saber.
            if (str_contains($e->getFile(), 'vendor\laravel\framework')) {
                // return; // Sugestão: Comente este return se quiser depurar erros internos do Laravel no futuro
            }
            if (str_contains($e->getMessage(), 'Unauthenticated')) {
                return; // Sugestão: Comente este return se quiser depurar erros de autenticação no futuro
            }

            if (str_contains($e->getMessage(), 'The route images/favicon.ico could not be found.')) {
                return; // Sugestão: Comente este return se quiser depurar erros relacionados a favicon no futuro
            }
            if (str_contains($e->getMessage(), 'CSRF token mismatch')) {
                return; // Sugestão: Comente este return se quiser depurar erros de token CSRF no futuro
            }
            try {

                if (app()->runningInConsole()) {
                    return;
                }
                $user = null;
                $username = 'Desconhecido';
                if (Auth::user()) {
                    $user = User::find(Auth::user()->id);
                    $username = $user->name;
                    Notification::make()
                        ->title('Ocorreu um erro inesperado')
                        ->warning()
                        ->send();
                }
                $admins = User::find(1);
                Notification::make()
                    ->title('Erro na aplicação')
                    ->body('O usuario: ' . $username . ' gerou o erro ' . $e->getMessage() . ' Arquivo: ' . $e->getFile() . ' linha ' . $e->getLine())
                    ->danger()
                    ->sendToDatabase($admins);

                if (
                    $e->getMessage() != 'Unauthenticated'
                    && $e->getMessage() != 'The route images/favicon.ico could not be found.'
                ) {
                    logger()->error('Erro capturado globalmente', [
                        'message' => $e->getMessage(),
                        'full_url' => request()->fullUrl(),
                        'method' => request()->method(),
                        'path' => request()->path(),
                        'request_uri' => request()->server('REQUEST_URI'),
                        'referer' => request()->headers->get('referer'),
                        'origin' => request()->headers->get('origin'),
                        'user_agent' => request()->userAgent(),
                        'x_livewire' => request()->headers->get('x-livewire'),
                        'content_type' => request()->headers->get('content-type'),
                    ]);
                }
            } catch (\Throwable $notifyException) {
                Log::error('Falha ao notificar exceção', ['erro' => $notifyException->getMessage()]);
            }
        });
    })->create();
