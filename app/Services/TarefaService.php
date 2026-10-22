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

class TarefaService
{

    public static function gerenciarTarefas($novo, $antigo)
    {
        if ($antigo->status_id != $novo->status_id) {
            if ($novo->status_id === config('settings.tarefastatus.andamento')) {
                $novo->data_iniciada = now();
            }
            if ($novo->status_id === config('settings.tarefastatus.concluida')) {
                $novo->data_concluida = now();
            }
        }

        if ($antigo->registrofuncional_id !== $novo->registrofuncional_id) {
            if ($novo->registrofuncional_id != null) {
                if (!$antigo->status_id || $antigo->status_id == config('settings.tarefastatus.pendente')) {
                    $novo->status_id = config('settings.tarefastatus.atribuida');
                }
                self::notificarResponsavel($novo);
            } else {
                $novo->status_id = config('settings.tarefastatus.pendente');
            }
        }
    }


    public static function syncTarefas(RegistroFuncional $registro, array $tarefasIds): void
    {
        try {
            DB::beginTransaction();
            $demandaId = $registro->demanda_id;

            // Normaliza ids
            $selected = [];
            foreach ($tarefasIds as $k => $id) {
                if (is_numeric($id)) {
                    $selected[] = (int) $id;
                }
            }
            Tarefas::where('registrofuncional_id', $registro->id)
                ->whereNotIn('id', $selected)
                ->update(['registrofuncional_id' => null]);
            if (! empty($selected)) {
                // Vincula apenas tarefas válidas (mesma demanda)
                Tarefas::whereIn('id', $selected)->get();
                // ->where('demanda_id', $demandaId)
                foreach (Tarefas::whereIn('id', $selected)->get() as $tarefa) {
                    $tarefa->registrofuncional_id = $registro->id;
                    $tarefa->save();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao sincronizar tarefas: ' . $e->getMessage());
        }
    }

    public static function notificarResponsavel($tarefa)
    {
        $responsavel = User::find(RegistroFuncional::find($tarefa->registrofuncional_id)->pessoa);
        $demanda = Demanda::find($tarefa->demanda_id);
        if ($responsavel) {
            $mensagem = "Você foi designado como responsável pela tarefa: {$tarefa->descricao} da demanda: {$demanda->titulo}.";
            Notification::make()
                ->title('Nova Tarefa')
                ->body($mensagem)
                ->info()
                ->sendToDatabase($responsavel);
            //talvez enviar email
            // Mail::to($responsavel->email)->send(new TarefaMail($tarefa));
        }
    }
}
