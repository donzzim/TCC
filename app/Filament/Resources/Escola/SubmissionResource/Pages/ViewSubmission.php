<?php

namespace App\Filament\Resources\Escola\SubmissionResource\Pages;

use App\Filament\Resources\Escola\SubmissionResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;
    protected string $view = 'filament.resources.submission-resource.pages.view-submission';

    public static function getResultados(): array
    {
        $dados = DB::select("SELECT IFNULL(ff.label, 'Total') Perguntas,
                    ff.order,
                    COUNT(case fsf.value WHEN 'Ótimo' then 1 end) as Otimo,
                    COUNT(case fsf.value WHEN 'Bom' then 1 end) as Bom,
                    COUNT(case fsf.value WHEN 'Ruim' then 1 end) as Ruim,
                    COUNT(case fsf.value WHEN 'Péssimo' then 1 end) as Pessimo,
                    COUNT(case fsf.value WHEN 'Totalmente' then 1 end) as Totalmente,
                    COUNT(case fsf.value WHEN 'Parcialmente' then 1 end) as Parcialmente,
                    COUNT(case fsf.value WHEN 'Sem resposta' then 1 end) as SemResposta,
                    count(*) Total
                FROM esc_formsubmissions fs
                    INNER JOIN esc_formsubmissionfields fsf ON fs.id = fsf.form_submission_id
                    INNER JOIN esc_formfields ff on fsf.form_field_id = ff.id
                group by IFNULL(ff.label, 'Total'), ff.order
                order by 2");
        return $dados;
    }
    public function getHeading(): string
    {
        return 'Respostas do Formulário: ' . $this->record->curso->titulo;
    }

    public function getSubheading(): string
    {
        return 'Enviado em: ' . $this->record->created_at->format('d/m/Y H:i');
    }
}
