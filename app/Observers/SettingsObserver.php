<?php

namespace App\Observers;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Settings $setting): void
    {
        Cache::forget('settings');
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Settings $setting): void
    {
        Cache::forget('settings');
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Settings $setting): void
    {
        Cache::forget('settings');
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Settings $setting): void
    {
        //
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Settings $setting): void
    {
        //
    }
}
