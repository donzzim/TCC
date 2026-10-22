<?php

namespace App\Services\Pedagogico;

use App\Http\Controllers\PrincipalController;
use App\Models\Administrativo\Agenda;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FrequenciaService
{

    public static function getAgendaFrequencia(Cursos | CursoAtividades $curso): ?Agenda
    {
        $datahoje = Carbon::now('America/Sao_Paulo');
        $toleranciaInicio = $curso->inicio_tolerancia;
        $toleranciaTermino = $curso->termino_tolerancia;

        if ($curso->temAtividades()) {
            $atividadesId = $curso->atividades->pluck('id');
            $agenda = Agenda::whereIn('agendaable_id', $atividadesId)->where('inicio', '<=', $datahoje->copy()->addMinutes($toleranciaInicio))
                ->where('termino', '>=', $datahoje->copy()->subMinutes($toleranciaTermino))->first();
        } else {
            $agenda = $curso->agendas()
                ->where('inicio', '<=', $datahoje->copy()->addMinutes($toleranciaInicio))
                ->where('termino', '>=', $datahoje->copy()->subMinutes($toleranciaTermino))
                ->first();
        }

        // dd($agenda, $datahoje,  $datahoje->copy()->addMinutes($toleranciaInicio), $datahoje->copy()->subMinutes($toleranciaTermino), $curso->agendas()->get());

        return $agenda;
    }


    public static function gerarFrequencias($curso, $aluno)
    {
        Log::info("gerando/atualizando frequencias para o aluno {$aluno->name}");

        foreach ($curso->agendas as $agenda) {
            Log::info("Gerando/Atualizando frequência para o aluno {$aluno->name} : ID {$aluno->id} na agenda {$agenda->id} do curso {$curso->titulo} : ID {$curso->id}");
            self::sincronizarFrequenciaDaAgenda($agenda, $curso, $aluno);
        }
        return;
    }

    /**
     * Garante que uma agenda tenha uma frequência para cada inscrito ativo.
     * Registros existentes são mantidos para não perder o status já lançado.
     */
    public static function sincronizarFrequenciasDaAgenda(Agenda $agenda): Collection
    {
        $evento = $agenda->agendaable;

        if (!$evento || !method_exists($evento, 'alunos')) {
            return collect();
        }

        $alunosIds = $evento->alunos()
            ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
            ->pluck('aluno_id');

        $alunosIds = $alunosIds
            ->merge($agenda->frequencias()->pluck('aluno_id'))
            ->filter()
            ->unique()
            ->values();

        $alunos = User::whereIn('id', $alunosIds)->get();

        foreach ($alunos as $aluno) {
            self::sincronizarFrequenciaDaAgenda($agenda, $evento, $aluno);
        }

        return $alunos;
    }

    private static function sincronizarFrequenciaDaAgenda(Agenda $agenda, $evento, User $aluno): void
    {
        CursoFrequencias::updateOrCreate(
            [
                'aluno_id' => $aluno->id,
                'agenda_id' => $agenda->id,
            ],
            [
                'aluno_id' => $aluno->id,
                'agenda_id' => $agenda->id,
                'curso_id' => $evento->id,
                'data_frequencia' => $agenda->inicio,
                'atualizado_por' => Auth::id() ?? $aluno->id,
            ]
        );
    }

    public static function reconciliarFrequencias(Agenda $agenda)
    {
        $evento = $agenda->agendaable;

        if ($evento) {

            $frequencias = $agenda->frequencias()->with('aluno')->get();

            // dd($frequencias);

            $users = $frequencias->map(function ($freq) {
                return $freq->aluno ?? null;
            })->filter()->unique('id')->values();
            // dd($users);

            self::sincronizarFrequenciasDaAgenda($agenda);

            Log::info('Entrando em INSCREVER');

            if ($users->isNotEmpty()) {
                InscricaoService::inscrever($evento, $users, false, null, false);
            }


            Log::info("Alunos únicos para agenda {$agenda->id}: " . $users->pluck('id')->implode(','));
        }
    }


    public static function atualizaFrequencias($curso): void
    {
        foreach ($curso->agendas()->get() as $agenda) {
            self::sincronizarFrequenciasDaAgenda($agenda);
        }
    }


    public static function limparFrequencias($aluno): void
    {
        $aluno->frequencias()->delete();
        return;
    }
}
