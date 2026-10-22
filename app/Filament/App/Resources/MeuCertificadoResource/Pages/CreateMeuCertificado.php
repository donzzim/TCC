<?php

namespace App\Filament\App\Resources\MeuCertificadoResource\Pages;

use App\Filament\App\Resources\MeuCertificadoResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateMeuCertificado extends CreateRecord
{
    protected static string $resource = MeuCertificadoResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['curso_id']) && $data['curso_id'] == 0) {
            $data['observacao'] = json_encode([
                'nome' => $data['curso_outros_nome'] ?? '',
                'instituicao' => $data['instituicao'] ?? '',
                'aprovacao' => $data['curso_outros_aprovacao'] ?? '',
                'carga_horaria' => $data['curso_outros_carga_horaria'] ?? '',
            ]);
        }
        $data['modelo_id'] = 3;
        $data['pessoa_id'] = Filament::auth()->id();

        return $data;
    }
}
