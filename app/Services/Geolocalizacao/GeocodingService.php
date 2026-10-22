<?php

namespace App\Services\Geolocalizacao;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public static function buscarLocalizacaoPorCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        $endereco = static::consultarCep($cep);

        if (! $endereco) {
            return null;
        }

        $enderecoCompleto = static::montarEnderecoCompleto($endereco);
        $latitude = static::normalizarCoordenada($endereco['latitude'] ?? null);
        $longitude = static::normalizarCoordenada($endereco['longitude'] ?? null);

        if ((! $latitude || ! $longitude) && filled($enderecoCompleto)) {
            $coordenadas = static::geocodificarEndereco($endereco, $enderecoCompleto);
            $latitude = $coordenadas['latitude'] ?? $latitude;
            $longitude = $coordenadas['longitude'] ?? $longitude;
        }

        return [
            'cep' => static::formatarCep($cep),
            'endereco_completo' => $enderecoCompleto,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'endereco' => $endereco,
        ];
    }

    public static function formatarCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return $cep;
        }

        return substr($cep, 0, 5) . '-' . substr($cep, 5);
    }

    protected static function consultarCep(string $cep): ?array
    {
        return static::consultarViaCep($cep);
    }

    protected static function consultarViaCep(string $cep): ?array
    {
        try {
            $data = Http::withOptions([
                'verify' => false,
                'timeout' => 20,
            ])
                ->acceptJson()
                ->get("https://viacep.com.br/ws/{$cep}/json")
                ->throw()
                ->json();

            if (! is_array($data) || ! empty($data['erro'])) {
                return null;
            }

            return [
                'cep' => $data['cep'] ?? $cep,
                'logradouro' => $data['logradouro'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cidade' => $data['localidade'] ?? null,
                'uf' => $data['uf'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function geocodificarEndereco(array $endereco, string $enderecoCompleto): array
    {
        $cacheKey = 'geocode:nominatim:' . md5($enderecoCompleto);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey, []);
        }

        $coordenadas = static::consultarNominatim([
            'street' => $endereco['logradouro'] ?? null,
            'city' => $endereco['cidade'] ?? null,
            'state' => $endereco['uf'] ?? null,
            'postalcode' => filled($endereco['cep'] ?? null) ? preg_replace('/\D/', '', (string) $endereco['cep']) : null,
            'country' => 'Brasil',
        ]);

        if (blank($coordenadas)) {
            $coordenadas = static::consultarNominatim([
                'q' => "{$enderecoCompleto}, Brasil",
            ]);
        }

        Cache::put($cacheKey, $coordenadas, blank($coordenadas) ? now()->addDay() : now()->addDays(30));

        return $coordenadas;
    }

    protected static function consultarNominatim(array $parametros): array
    {
        try {
            static::aguardarLimiteNominatim();

            $data = Http::withOptions([
                'verify' => false,
                'timeout' => 20,
            ])
                ->withHeaders([
                    'User-Agent' => static::getNominatimUserAgent(),
                    'Referer' => config('app.url'),
                ])
                ->acceptJson()
                ->get(config('services.nominatim.search_url', 'https://nominatim.openstreetmap.org/search'), array_filter([
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'br',
                    'addressdetails' => 1,
                    'accept-language' => 'pt-BR',
                    ...$parametros,
                ], fn ($valor) => filled($valor)))
                ->throw()
                ->json();

            if (! is_array($data) || blank($data[0] ?? null)) {
                return [];
            }

            return [
                'latitude' => static::normalizarCoordenada($data[0]['lat'] ?? null),
                'longitude' => static::normalizarCoordenada($data[0]['lon'] ?? null),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    protected static function aguardarLimiteNominatim(): void
    {
        $cacheKey = 'geocode:nominatim:last-request-at';
        $agora = microtime(true);
        $ultimaRequisicao = Cache::get($cacheKey);

        if (is_numeric($ultimaRequisicao)) {
            $intervalo = $agora - (float) $ultimaRequisicao;

            if ($intervalo < 1) {
                usleep((int) ((1 - $intervalo) * 1000000));
            }
        }

        Cache::put($cacheKey, microtime(true), now()->addMinute());
    }

    protected static function getNominatimUserAgent(): string
    {
        $appName = config('app.name', 'EMES');
        $appUrl = config('app.url');

        return filled($appUrl)
            ? "{$appName}/1.0 ({$appUrl})"
            : "{$appName}/1.0";
    }

    protected static function montarEnderecoCompleto(array $endereco): ?string
    {
        $cidadeUf = collect([
            $endereco['cidade'] ?? null,
            $endereco['uf'] ?? null,
        ])->filter()->implode(' - ');

        $partes = collect([
            $endereco['logradouro'] ?? null,
            $endereco['bairro'] ?? null,
            $cidadeUf ?: null,
            filled($endereco['cep'] ?? null) ? 'CEP ' . static::formatarCep((string) $endereco['cep']) : null,
        ])->filter();

        return $partes->isNotEmpty() ? $partes->implode(', ') : null;
    }

    protected static function normalizarCoordenada(mixed $coordenada): ?string
    {
        if (blank($coordenada)) {
            return null;
        }

        $coordenada = str_replace(',', '.', (string) $coordenada);

        return is_numeric($coordenada) ? $coordenada : null;
    }
}
