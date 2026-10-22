<?php

namespace App\Services;

use App\Http\Controllers\PrincipalController;
use App\Mail\AprovacaoMail;
use App\Mail\CertificadoMail;
use App\Mail\ComunicacaoMail;
use App\Mail\InscricaoMail;
use App\Models\Administrativo\Agenda;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Administrativo\Tarefas;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegistroFuncionalService
{
    public static function createOrUpdateFromArray(array $data)
    {
        $registrosFuncionais = $data['registros_funcionais'] ?? [];
        if (count($registrosFuncionais) > 0) {

            foreach ($data['registros_funcionais'] as $registroData) {

                $tarefasIds = $registroData['tarefas'] ?? [];
                $setorId = $registroData['setor_id'] ?? User::find($registroData['pessoa_id'])->setorPrincipal()->first()->id ?? null;

                if (isset($registroData['id'])) {
                    $registro = RegistroFuncional::find($registroData['id']);
                    if ($registro) {
                        $registro->update([
                            'curso_id' => $data['id'] ?? null,
                            'setor_id' => $setorId,
                            'demanda_id' => $registroData['demanda_id'],
                            'pessoa_id' => $registroData['pessoa_id'],
                            'inicio' => $registroData['inicio'],
                            'quantidade' => $registroData['quantidade'] ?? true,
                            'situacao' => $registroData['situacao'] ?? false,
                        ]);
                        TarefaService::syncTarefas($registro, $tarefasIds);
                    }
                } else {
                    $registro = RegistroFuncional::create([
                        'curso_id' => $data['id'] ?? null,
                        'tipo_id' => $registroData['tipo_id'] ?? null,
                        'setor_id' => $setorId,
                        'demanda_id' => $data['demanda_id'],
                        'pessoa_id' => $registroData['pessoa_id'],
                        'inicio' => $registroData['inicio'],
                        'quantidade' => $registroData['quantidade'] ?? true,
                        'situacao' => $registroData['situacao'] ?? false,
                    ]);
                    $registro->save();
                    TarefaService::syncTarefas($registro, $tarefasIds);
                }
            }
        }
        if (isset($data['id'])) {
            $registrosAtuais = collect($registrosFuncionais)->pluck('id')->filter()->toArray();
            RegistroFuncional::where('curso_id', $data['id'])
                ->whereNotIn('id', $registrosAtuais)
                ->each(function ($registro) {
                    $registro->delete();
                });
        }
    }
}
