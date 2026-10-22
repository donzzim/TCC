<?php

namespace App\Filament\Egap\Support;

use Illuminate\Support\Facades\Storage;

class LegacyFileUrl
{
    public static function resolve(?string $path, string $legacyBaseUrl): ?string
    {
        if (blank($path)) {
            return null;
        }

        $relativePath = ltrim($path, '/');

        if (Storage::disk('egap')->exists($relativePath)) {
            return Storage::disk('egap')->url($relativePath);
        }

        return rtrim($legacyBaseUrl, '/') . '/' . $relativePath;
    }
}
