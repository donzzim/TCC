<?php

namespace App\Services\Egap;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CepService
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    private const VIACEP_URL = 'https://viacep.com.br/ws';

    /**
     * @var array<string, string>
     */
    private const SIGLAS_UF = [
        'acre' => 'AC',
        'alagoas' => 'AL',
        'amapá' => 'AP',
        'amazonas' => 'AM',
        'bahia' => 'BA',
        'ceará' => 'CE',
        'distrito federal' => 'DF',
        'espírito santo' => 'ES',
        'goiás' => 'GO',
        'maranhão' => 'MA',
        'mato grosso' => 'MT',
        'mato grosso do sul' => 'MS',
        'minas gerais' => 'MG',
        'pará' => 'PA',
        'paraíba' => 'PB',
        'paraná' => 'PR',
        'pernambuco' => 'PE',
        'piauí' => 'PI',
        'rio de janeiro' => 'RJ',
        'rio grande do norte' => 'RN',
        'rio grande do sul' => 'RS',
        'rondônia' => 'RO',
        'roraima' => 'RR',
        'santa catarina' => 'SC',
        'são paulo' => 'SP',
        'sergipe' => 'SE',
        'tocantins' => 'TO',
    ];

    /**
     * Consulta um CEP, priorizando o OpenStreetMap (Nominatim), que fornece
     * também latitude/longitude, e usando o ViaCEP como alternativa caso o
     * OpenStreetMap não retorne resultado.
     *
     * @return array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}|null
     */
    public function buscar(string $cep): ?array
    {
        $cepNormalizado = $this->normalizarCep($cep);

        if (! $this->cepValido($cepNormalizado)) {
            return null;
        }

        $endereco = $this->buscarNoOpenStreetMap($cepNormalizado);

        if ($endereco === null) {
            return $this->buscarNoViaCep($cepNormalizado);
        }

        if ($endereco['logradouro'] === '') {
            $endereco = $this->complementarComViaCep($endereco, $cepNormalizado);
        }

        return $endereco;
    }

    /**
     * O Nominatim (OpenStreetMap) indexa CEPs brasileiros por bairro/área, não
     * por logradouro, então normalmente não retorna a rua. Quando isso
     * acontece, complementamos os campos vazios com o ViaCEP, preservando a
     * latitude/longitude já obtidas do OpenStreetMap.
     *
     * @param  array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}  $endereco
     * @return array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}
     */
    private function complementarComViaCep(array $endereco, string $cepNormalizado): array
    {
        $viaCep = $this->buscarNoViaCep($cepNormalizado);

        if ($viaCep === null) {
            return $endereco;
        }

        foreach (['logradouro', 'complemento', 'bairro', 'cidade', 'uf', 'ibge', 'ddd'] as $campo) {
            if ($endereco[$campo] === '') {
                $endereco[$campo] = $viaCep[$campo];
            }
        }

        return $endereco;
    }

    /**
     * @return array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}|null
     */
    private function buscarNoOpenStreetMap(string $cepNormalizado): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->get(self::NOMINATIM_URL, [
                    'postalcode' => $this->formatarComTraco($cepNormalizado),
                    'country' => 'Brazil',
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 1,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Falha ao consultar OpenStreetMap', ['cep' => $cepNormalizado, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $dados = $response->json();

        if (! is_array($dados) || ! isset($dados[0]) || ! is_array($dados[0])) {
            return null;
        }

        return $this->formatarOpenStreetMap($dados[0], $cepNormalizado);
    }

    /**
     * @return array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}|null
     */
    private function buscarNoViaCep(string $cepNormalizado): ?array
    {
        try {
            $response = Http::timeout(5)->get(self::VIACEP_URL."/{$cepNormalizado}/json/");
        } catch (ConnectionException $e) {
            Log::warning('Falha ao consultar ViaCEP', ['cep' => $cepNormalizado, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $dados = $response->json();

        if (! is_array($dados) || ! empty($dados['erro'])) {
            return null;
        }

        return $this->formatarViaCep($dados);
    }

    /**
     * @param  array<string, mixed>  $local
     * @return array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}
     */
    private function formatarOpenStreetMap(array $local, string $cepNormalizado): array
    {
        $endereco = is_array($local['address'] ?? null) ? $local['address'] : [];

        return [
            'cep' => (string) ($endereco['postcode'] ?? $this->formatarComTraco($cepNormalizado)),
            'logradouro' => (string) ($endereco['road'] ?? ''),
            'complemento' => '',
            'bairro' => (string) ($endereco['suburb'] ?? $endereco['neighbourhood'] ?? $endereco['city_district'] ?? ''),
            'cidade' => (string) ($endereco['city'] ?? $endereco['town'] ?? $endereco['village'] ?? $endereco['municipality'] ?? ''),
            'uf' => $this->siglaUf((string) ($endereco['state'] ?? '')),
            'ibge' => '',
            'ddd' => '',
            'latitude' => (string) ($local['lat'] ?? ''),
            'longitude' => (string) ($local['lon'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array{cep:string,logradouro:string,complemento:string,bairro:string,cidade:string,uf:string,ibge:string,ddd:string,latitude:string,longitude:string}
     */
    private function formatarViaCep(array $dados): array
    {
        return [
            'cep' => (string) ($dados['cep'] ?? ''),
            'logradouro' => (string) ($dados['logradouro'] ?? ''),
            'complemento' => (string) ($dados['complemento'] ?? ''),
            'bairro' => (string) ($dados['bairro'] ?? ''),
            'cidade' => (string) ($dados['localidade'] ?? ''),
            'uf' => (string) ($dados['uf'] ?? ''),
            'ibge' => (string) ($dados['ibge'] ?? ''),
            'ddd' => (string) ($dados['ddd'] ?? ''),
            'latitude' => '',
            'longitude' => '',
        ];
    }

    private function siglaUf(string $estado): string
    {
        if (strlen($estado) === 2) {
            return strtoupper($estado);
        }

        return self::SIGLAS_UF[mb_strtolower($estado)] ?? '';
    }

    private function userAgent(): string
    {
        return config('app.name').' ('.config('app.url').')';
    }

    private function normalizarCep(string $cep): string
    {
        return preg_replace('/\D+/', '', $cep) ?? '';
    }

    private function formatarComTraco(string $cepNormalizado): string
    {
        return substr($cepNormalizado, 0, 5).'-'.substr($cepNormalizado, 5);
    }

    private function cepValido(string $cep): bool
    {
        return preg_match('/^\d{8}$/', $cep) === 1;
    }
}
