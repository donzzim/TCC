<?php

namespace App\Providers;

use App\Services\Auto\Support\StepExecutionFactory;
use App\Services\Auto\Support\StepStrategyFactory;
use App\Services\Egap\LivewireComponentsService;
use App\Auth\HybridUserProvider;
use App\Services\Auth\TjesLdapService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        } else {
            URL::forceScheme('http');
        }

        $this->app->singleton(StepStrategyFactory::class, function ($app) {
            return new StepStrategyFactory();
        });

        $this->app->singleton(StepExecutionFactory::class, function ($app) {
            return new StepExecutionFactory();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('mobile-login', fn ($request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('login')) . '|' . $request->ip()));

        Auth::provider('hybrid', function ($app, array $config): HybridUserProvider {
            return new HybridUserProvider(
                $app['hash'],
                // O provider híbrido é exclusivo do usuário do EMES. Não
                // permita que AUTH_MODEL ou config antigo altere este model.
                \App\Models\User::class,
                $app->make(TjesLdapService::class),
            );
        });

        Blade::anonymousComponentPath(
            resource_path('views/egap/relatorios/components'),
            'relatorios',
        );

        Blade::anonymousComponentPath(
            resource_path('views/egap/impressao/components'),
            'impressao',
        );

        $livewireRegistrationStart = microtime(true);
        LivewireComponentsService::getLivewireComponents();
        $livewireRegistrationMs = round((microtime(true) - $livewireRegistrationStart) * 1000, 2);

        if ($livewireRegistrationMs >= 100) {
            logger()->warning('BOOT_STAGE_SLOW', [
                'stage' => 'AppServiceProvider.livewire_components',
                'duration_ms' => $livewireRegistrationMs,
            ]);
        }

        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            return str_replace('Models', 'Policies', $modelClass) . 'Policy';
        });
    }
}
