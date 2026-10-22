<?php

namespace App\Filament\Egap\Helper;

use Illuminate\Support\Facades\Storage;

class ImagemPathHelper
{
    public static function resolve(?string $imagem, string $disk = 'egap'): ?string
    {
        $path = self::extractPath($imagem);

        if ($path === null) {
            return null;
        }

        return Storage::disk($disk)->exists($path) ? $path : null;
    }

    public static function resolveComFallback(?string $imagem, string $fallback = 'placeholder.svg', string $disk = 'egap'): string
    {
        return self::resolve($imagem, $disk) ?? $fallback;
    }

    protected static function extractPath(?string $imagem): ?string
    {
        if (! is_string($imagem) || trim($imagem) === '') {
            return null;
        }

        $decoded = json_decode($imagem);

        $path = is_array($decoded) && ! empty($decoded[0]->file)
            ? (string) $decoded[0]->file
            : $imagem;

        $path = ltrim($path, '/');

        return $path !== '' ? $path : null;
    }
}
