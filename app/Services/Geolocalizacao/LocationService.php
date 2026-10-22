<?php

namespace App\Services\Geolocalizacao;

class LocationService
{
    public static function shouldValidateProximity(mixed $local = null): bool
    {
        if (static::isFalseValue(config('settings.localizacao.ativa'))) {
            return false;
        }
        $remoto = $local->json['remoto'] ?? null;
        if ($remoto) {
            return false;
        }

        return true;
    }



    public static function distanceInMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000;

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function validateProximity(
        ?float $userLat,
        ?float $userLng,
        ?int $accuracy,
        ?float $targetLat,
        ?float $targetLng,
        int $radiusMeters = 150,
        int $maxAccuracy = 100
    ): array {
        if (! $userLat || ! $userLng) {
            return [
                'allowed' => false,
                'message' => 'Localização não capturada.',
                'distance' => null,
            ];
        }

        if (! $targetLat || ! $targetLng) {
            return [
                'allowed' => false,
                'message' => 'Localização do evento não configurada.',
                'distance' => null,
            ];
        }

        if ($accuracy && $accuracy > $maxAccuracy) {
            return [
                'allowed' => false,
                'message' => "Localização imprecisa. Precisão atual: {$accuracy}m.",
                'distance' => null,
            ];
        }

        $distance = self::distanceInMeters(
            $userLat,
            $userLng,
            $targetLat,
            $targetLng
        );

        return [
            'allowed' => $distance <= $radiusMeters,
            'message' => $distance <= $radiusMeters
                ? 'Localização validada.'
                : 'Você está fora do raio permitido.',
            'distance' => round($distance),
        ];
    }

    protected static function isFalseValue(mixed $value): bool
    {
        if ($value === false || $value === 0 || $value === '0') {
            return true;
        }

        return is_string($value) && strtolower($value) === 'false';
    }
}
