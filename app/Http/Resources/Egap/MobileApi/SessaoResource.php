<?php

namespace App\Http\Resources\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property MobileEgapSession $resource
 */
class SessaoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return $this->resource->paraResposta();
    }
}
