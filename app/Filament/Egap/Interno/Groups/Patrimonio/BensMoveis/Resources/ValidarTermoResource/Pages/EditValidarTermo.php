<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use Filament\Resources\Pages\EditRecord;

class EditValidarTermo extends EditRecord
{
    protected static string $resource = ValidarTermoResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['arquivo_digital'] = ArquivoDigital::caminhoArquivoDigitalNoDisco(
            $data['arquivo_digital'] ?? null,
        );

        return $data;
    }
}
