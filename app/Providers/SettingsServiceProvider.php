<?php

namespace App\Providers;

use App\Models\Settings;
use App\Observers\SettingsObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Settings::observe(SettingsObserver::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $settingsBootStart = microtime(true);

        // if ($this->app->runningInConsole()) {
        //     return;
        // }

        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $settings = Cache::rememberForever('settings', function () {
                return Settings::all()->pluck('value', 'key')->all();
            });

            Config::set('settings', $settings);
        } catch (\Exception $e) {
            report($e);
        }

        $settingsBootMs = round((microtime(true) - $settingsBootStart) * 1000, 2);

        if ($settingsBootMs >= 100) {
            logger()->warning('BOOT_STAGE_SLOW', [
                'stage' => 'SettingsServiceProvider.boot',
                'duration_ms' => $settingsBootMs,
            ]);
        }
    }
}
