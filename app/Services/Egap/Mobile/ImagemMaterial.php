<?php

namespace App\Services\Egap\Mobile;

use App\Filament\Egap\Helper\ImagemPathHelper;

/**
 * URL pública da foto de um material para o app (servida em /storage/egap).
 *
 * A URL sai do protocolo e host da requisição, então funciona igual no
 * domínio de produção, no IP da rede local e atrás do ngrok.
 */
final class ImagemMaterial
{
    /**
     * Primeira imagem que existe no disco entre as candidatas, na ordem dada
     * (ex.: a da descrição detalhada antes da resumida).
     */
    public static function url(?string ...$imagens): ?string
    {
        foreach ($imagens as $imagem) {
            $path = ImagemPathHelper::resolve($imagem);

            if ($path !== null) {
                $segmentos = array_map('rawurlencode', explode('/', $path));
                $relativo = '/storage/egap/'.implode('/', $segmentos);

                // url() seguiria o URL::forceScheme do AppServiceProvider (http
                // em ambiente local) e o app atrás do ngrok/https pediria a foto
                // em http; aqui valem o protocolo e o host reais da requisição.
                return app()->runningInConsole() && ! app()->runningUnitTests()
                    ? url($relativo)
                    : request()->getSchemeAndHttpHost().request()->getBaseUrl().$relativo;
            }
        }

        return null;
    }
}
