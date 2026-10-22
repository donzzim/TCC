<?php

namespace App\Services;

use App\Models\Administrativo\Agenda;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgendaService
{
    public static function buscarAgendas($evento): Collection
    {
        if (method_exists($evento, 'temAtividades') && $evento->temAtividades()) {
            $agendas = $evento->agendas()->get();

            foreach ($evento->atividades()->with('agendas')->get() as $atividade) {
                $agendas = $agendas->merge($atividade->agendas);
            }

            return $agendas->sortBy('inicio')->values();
        }

        return $evento->agendas()->orderBy('inicio')->get();
    }

    public static function sincronizarAgendaInformativa($evento): bool
    {
        if (!method_exists($evento, 'agendas')) {
            return false;
        }

        $periodo = self::periodoAgendaInformativa($evento);

        if (!$periodo) {
            $evento->agendas()->delete();
            return true;
        }

        $dados = self::dadosAgendaInformativa($evento, $periodo['inicio'], $periodo['termino']);
        $agenda = $evento->agendas()->orderBy('id')->first();

        if ($agenda) {
            $agenda->update($dados);
            $evento->agendas()
                ->where('id', '!=', $agenda->id)
                ->delete();

            return true;
        }

        $evento->agendas()->create($dados);

        return true;
    }

    public static function realocarAgenda($record, $data)
    {
        $diasCalendario = data_get($data, 'periodo_selecionado.dias', []);

        if (!empty($diasCalendario)) {
            self::realocarAgendaPeloCalendario($record, $data, $diasCalendario);
            return;
        }

        self::realocarAgendaPeloRepeater($record, $data);
    }

    protected static function realocarAgendaPeloCalendario($record, array $data, array $dias): void
    {
        $agendaableId = data_get($data, 'periodo_selecionado.agendaable_id') ?? data_get($data, 'agendaable') ?? $record->id;
        $agendaable = self::resolverAgendaable($record, $agendaableId);

        DB::beginTransaction();

        try {
            $ok = self::agendarCurso($agendaable, [
                'dias' => $dias,
                'modo' => data_get($data, 'periodo_selecionado.modo_persistencia', 'operacoes'),
            ]);

            if (!$ok) {
                DB::rollBack();
                return;
            }

            self::atualizarInicioTermino($record);
            DB::commit();

            Notification::make()
                ->title('Agendamento(s) atualizado(s) com sucesso.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao realocar agenda pelo calendario.', [
                'record_id' => $record->id,
                'agendaable_id' => $agendaableId,
                'message' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Erro ao atualizar agendamento.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function realocarAgendaPeloRepeater($record, $data)
    {
        $agendasData = $data['agendas'] ?? [];
        $agendasId = array_column($agendasData, 'id');
        $agendas = self::buscarAgendas($record);
        $now = Carbon::now();
        // dd($now);

        DB::beginTransaction();

        foreach ($agendas as $agenda) {
            $agendaInicio = Carbon::parse($agenda->inicio);

            //se foi removido no repeater
            if (!in_array($agenda->id, $agendasId)) {
                if (!self::excluirAgendaSePermitido($agenda)) {
                    DB::rollBack();
                    return;
                }
                continue;
            }

            //se foi alterado no repeater
            $a = collect($agendasData)->firstWhere('id', $agenda->id);
            if (!$a) {
                continue;
            }

            $dataInicio = Carbon::parse($a['data'] . ' ' . $a['hora_inicio'])->seconds(0)->timezone(config('app.timezone'));
            $dataTermino = Carbon::parse($a['data'] . ' ' . $a['hora_termino'])->seconds(0)->timezone(config('app.timezone'));

            if (!self::periodoValido($dataInicio, $dataTermino)) {
                DB::rollBack();
                return;
            }

            $agendaInicio = Carbon::parse($agenda->inicio)->seconds(0)->timezone(config('app.timezone'));
            $agendaTermino = Carbon::parse($agenda->termino)->seconds(0)->timezone(config('app.timezone'));

            if (!$dataInicio->equalTo($agendaInicio) || !$dataTermino->equalTo($agendaTermino) || $agenda->agendaable_id != $a['agendaable_id']) {

                Log::info("{$agenda->id}");

                $atividade = self::resolverAgendaable($record, $a['agendaable_id']);

                if (self::verificaConflito($atividade->tipolocal_id ?? $record->tipolocal_id, $dataTermino, $dataInicio, [$agenda->id])) {
                    DB::rollBack();
                    return;
                }

                Log::info('Atualizando agenda');
                $agenda->update([
                    'titulo'        => 'Curso',
                    'descricao'     => $atividade->titulo,
                    'inicio'        => $dataInicio,
                    'termino'       => $dataTermino,
                    'local_id'      => $atividade->tipolocal_id,
                    'agendaable_id' => $atividade->id,
                    'agendaable_type' => get_class($atividade),
                    'atualizado_por' => Auth::id(),
                ]);
                Log::info("{$agenda}");



                // $agenda->inicio = $dataInicio;
                // $agenda->termino = $dataTermino;
                // $agenda->agendaable_id = $atividade->id;
                // $agenda->agendaable_type = get_class($atividade);
                // $agenda->save();
                Log::info("Agenda {$agenda->id} atualizada para a atividade {$atividade->id} ({$atividade->titulo}) no dia {$dataInicio->toDateString()}.");
            }
        }


        //criar novos agendamentos
        foreach ($agendasData as $a) {
            if (!in_array($a['id'] ?? null, $agendas->pluck('id')->toArray())) {
                $dataInicio = Carbon::parse($a['data'] . ' ' . $a['hora_inicio']);
                $dataTermino = Carbon::parse($a['data'] . ' ' . $a['hora_termino']);

                $atividade = self::resolverAgendaable($record, $a['agendaable_id']);

                if (!self::periodoValido($dataInicio, $dataTermino)) {
                    DB::rollBack();
                    return;
                }

                if (self::verificaConflito($atividade->tipolocal_id ?? $record->tipolocal_id, $dataTermino, $dataInicio, [])) {
                    DB::rollBack();
                    return;
                }

                $atividade->agendas()->create([
                    'titulo'        => 'Curso',
                    'descricao'     => $atividade->titulo,
                    'inicio'        => $dataInicio,
                    'termino'       => $dataTermino,
                    'local_id'      => $atividade->tipolocal_id,
                    'atualizado_por' => Auth::id(),
                ]);

                Log::info("Nova agenda criada para a atividade {$atividade->id} ({$atividade->titulo}) no dia {$dataInicio->toDateString()}.");
                // FrequenciaService::atualizaFrequencias($atividade);
            }
        }


        self::atualizarInicioTermino($record);
        DB::commit();
        Notification::make()
            ->title('Agendamento(s) atualizado(s) com sucesso.')
            ->success()
            ->send();
    }

    // Não pode ser chamado dentro de um booted de cursos ou atividades
    public static function atualizarInicioTermino($curso): void
    {
        Log::info("Atualizando início e término para o curso {$curso->id} - {$curso->titulo}");
        if ($curso->temAtividades()) {
            Log::info("Curso tem atividades, buscando agendas das atividades...");
            foreach ($curso->atividades as $atividade) {
                self::atualizarDatasDoCurso($atividade);
            }

            self::atualizarDatasDoCurso($curso);
            return;
        }

        Log::info("Curso não tem atividades, buscando agendas diretas do curso...");

        self::atualizarDatasDoCurso($curso);

        if ($curso->curso_id) {
            Log::info("Curso é filho de outro curso, atualizando início e término do curso pai...");
            self::atualizarDatasDoCurso($curso->curso);
        }
    }

    protected static function atualizarDatasDoCurso($curso): void
    {
        $agendas = self::buscarAgendas($curso);

        if ($agendas->isEmpty()) {
            return;
        }

        $inicio = $agendas->min('inicio');
        $termino = $agendas->max('termino');

        $curso::withoutEvents(function () use ($curso, $inicio, $termino) {
            $curso::withoutGlobalScopes()
                ->whereKey($curso->id)
                ->update([
                    'inicio' => $inicio,
                    'termino' => $termino,
                ]);
        });
    }


    protected static function agendaablesDoRecord($record): Collection
    {
        $agendaables = collect();

        if ($record->temAtividades()) {
            $agendaables = $record->atividades()->get();
        }

        return $agendaables->push($record);
    }

    protected static function resolverAgendaable($record, $agendaableId)
    {
        return self::agendaablesDoRecord($record)
            ->first(fn($agendaable) => (string) $agendaable->id === (string) $agendaableId)
            ?? $record;
    }

    protected static function periodoValido(Carbon $inicio, Carbon $termino): bool
    {
        if ($termino->greaterThan($inicio)) {
            return true;
        }

        Notification::make()
            ->title('Periodo invalido')
            ->body('O horario de termino precisa ser maior que o horario de inicio.')
            ->danger()
            ->send();

        return false;
    }

    protected static function excluirAgendaSePermitido(Agenda $agenda): bool
    {
        if ($agenda->frequencias()
            ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
            ->exists()
        ) {
            Notification::make()
                ->title('Operacao nao permitida')
                ->body('Nao e possivel excluir agendamento com frequencia presente vinculada.')
                ->danger()
                ->send();

            Log::info("Agenda {$agenda->id} com frequencias presentes vinculadas, pulando a delecao");

            return false;
        }

        $agenda->delete();

        return true;
    }

    public static function verificaConflito($local_id, $termino, $inicio, array $agenda_id = [],  array $paiId = []): bool
    {
        Log::info("Verificando conflito para local_id: {$local_id}, inicio: {$inicio}, termino: {$termino}", ['agenda_id' => $agenda_id, 'paiId' => $paiId]);
        if (blank($local_id)) {
            Log::info("Local nao informado, ignorando verificacao de conflito.");
            return false;
        }

        if ($local_id == 3412 || $local_id == 3419) { // ID do "Local a definir ou local outros"
            Log::info("Local é 'a definir' ou 'outros', ignorando verificação de conflito.");
            return false;
        }

        try {
            $inicioCarbon = Carbon::parse($inicio);
            $terminoCarbon = Carbon::parse($termino);
            $horaInicio = $inicioCarbon->format('H:i:s');
            $horaTermino = $terminoCarbon->format('H:i:s');

            Log::info("Início do evento: {$inicioCarbon}, Término do evento: {$terminoCarbon}");
            Log::info("Hora início: {$horaInicio}, Hora término: {$horaTermino}");



            $eventosConflitantes = Agenda::where('local_id', $local_id)
                ->where('termino', '>', $inicioCarbon)
                ->where('inicio', '<', $terminoCarbon)
                ->whereNotIn('id', $agenda_id)
                ->whereNotIn('agendaable_id', $paiId)
                ->where(function (Builder $query) {
                    $query
                        ->whereNull('agendaable_type')
                        ->orWhere('agendaable_type', '!=', Acao::class);
                })
                ->get();


            foreach ($eventosConflitantes as $eventoConflitante) {
                Log::info("Evento conflitante encontrado: " . ($eventoConflitante ? $eventoConflitante->id : 'Nenhum'));


                //verificar se horarios se sobrepoem

                if (!($horaTermino <= Carbon::parse($eventoConflitante->inicio)->format('H:i:s') ||
                    $horaInicio >= Carbon::parse($eventoConflitante->termino)->format('H:i:s'))) {


                    // dd($eventoConflitante, $local_id, $inicioCarbon, $terminoCarbon, $horaInicio, $horaTermino, $agenda_id, $paiId);
                    $user = Auth::user();

                    Notification::make()
                        ->title('Conflito de Agenda')
                        ->body(sprintf(
                            'O evento "%s" já ocupa o horário de %s até %s no local "%s". O agendamento não pode ser realizado.',
                            $eventoConflitante->descricao,
                            Carbon::parse($eventoConflitante->inicio)->format('d/m H:i'),
                            Carbon::parse($eventoConflitante->termino)->format('d/m H:i'),
                            $eventoConflitante->local->descricao
                        ))
                        ->danger()
                        ->persistent()
                        ->send()
                        ->sendToDatabase($user);

                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            $user = Auth::user();
            $admins = User::role('super_admin', 'web')->get();

            Notification::make()
                ->title("Erro na validação de agendamento do usuário {$user->name}")
                ->body('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine())
                ->danger()
                ->sendToDatabase($admins);

            Notification::make()
                ->title('Ocorreu um erro inesperado ao tentar agendar.')
                ->danger()
                ->sendToDatabase($user);

            return false;
        }
    }



    public static function agendarCurso($evento, $dias = []): bool
    {
        $payload = self::normalizarPayloadAgendamento($evento, $dias);
        $dias = $payload['dias'];
        $modo = $payload['modo'];
        $agendasAtuais = $evento->agendas()->get()->keyBy('id');
        $idsRecebidos = collect();


        foreach ($dias as $dia) {
            if (!is_array($dia)) {
                continue;
            }

            $dia = self::normalizarDiaAgendamento($evento, $dia);

            if (!$dia) {
                continue;
            }

            $agendaId = $dia['id'] ?? null;
            $operacao = $dia['operacao'];
            $agenda = $agendaId ? $agendasAtuais->get((int) $agendaId) : null;

            if ($agendaId) {
                $idsRecebidos->push((int) $agendaId);
            }

            if ($operacao === 'keep') {
                continue;
            }

            if ($operacao === 'delete') {
                if ($agenda && !self::excluirAgendaSePermitido($agenda)) {
                    return false;
                }

                continue;
            }

            if ($agendaId && !$agenda) {
                Log::warning("Agenda {$agendaId} ignorada: nao pertence ao agendaable {$evento->id}.");
                continue;
            }

            $dados = self::dadosAgendaParaDia($evento, $dia);
            $inicio = Carbon::parse($dados['inicio']);
            $termino = Carbon::parse($dados['termino']);

            if (!self::periodoValido($inicio, $termino)) {
                return false;
            }

            if (!$agenda) {
                $agenda = self::buscarAgendaEquivalente($agendasAtuais, $dados);

                if ($agenda) {
                    $idsRecebidos->push((int) $agenda->id);
                }
            }

            if (self::verificaConflito($dados['local_id'], $dados['termino'], $dados['inicio'], $agenda ? [$agenda->id] : [], self::paiIdsIgnorados($evento))) {
                return false;
            }

            if ($agenda) {
                $agenda->update($dados);
                Log::info("Agenda {$agenda->id} atualizada para o agendaable {$evento->id} ({$evento->titulo}) no dia {$dia['data']}.");
                continue;
            }

            $novaAgenda = $evento->agendas()->create($dados);
            Log::info("Agenda {$novaAgenda->id} criada para o agendaable {$evento->id} ({$evento->titulo}) no dia {$dia['data']}.");
        }

        if ($modo === 'replace_all') {
            foreach ($agendasAtuais as $agendaAtual) {
                if ($idsRecebidos->contains((int) $agendaAtual->id)) {
                    continue;
                }

                if (!self::excluirAgendaSePermitido($agendaAtual)) {
                    return false;
                }

                $inicioData = Carbon::parse($agendaAtual->inicio)->toDateString();
                Log::info("Agenda removida do dia {$inicioData}.");
            }
        }

        Log::info("Agendamento concluido para o agendaable {$evento->id} ({$evento->titulo}).");

        return true;
    }

    protected static function normalizarPayloadAgendamento($evento, $dias): array
    {
        $modo = 'operacoes';
        $listaExplicita = false;

        if ($dias instanceof Collection) {
            $dias = $dias->all();
        }

        if (is_array($dias) && array_key_exists('dias', $dias)) {
            $listaExplicita = true;
            $modo = $dias['modo'] ?? $modo;
            $dias = $dias['dias'] ?? [];
        }

        if ($dias instanceof Collection) {
            $dias = $dias->all();
        }

        if (empty($dias) && !$listaExplicita) {
            $dias = self::gerarDiasPadrao($evento);
        }

        return [
            'dias' => is_array($dias) ? array_values($dias) : [],
            'modo' => $modo,
        ];
    }

    protected static function gerarDiasPadrao($evento): array
    {
        if (blank($evento->inicio) || blank($evento->termino)) {
            return [];
        }

        $inicio = Carbon::parse($evento->inicio);
        $termino = Carbon::parse($evento->termino);
        $periodo = CarbonPeriod::create(
            $inicio->copy()->startOfDay(),
            $termino->copy()->startOfDay()
        );
        $dias = [];

        foreach ($periodo as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            $dias[] = [
                'data' => $date->toDateString(),
                'inicio' => $date->copy()->setTimeFrom($inicio)->toDateTimeString(),
                'termino' => $date->copy()->setTimeFrom($termino)->toDateTimeString(),
                'titulo' => 'Curso',
                'descricao' => $evento->titulo,
                'local_id' => $evento->tipolocal_id,
                'atualizado_por' => Auth::id(),
                'operacao' => 'create',
            ];
        }


        return $dias;
    }

    protected static function periodoAgendaInformativa($evento): ?array
    {
        $inicioBase = $evento->data_inicio ?? $evento->inicio ?? null;
        $terminoBase = $evento->data_termino ?? $evento->termino ?? $inicioBase;

        if (blank($inicioBase) && blank($terminoBase)) {
            return null;
        }

        $inicio = Carbon::parse($inicioBase ?? $terminoBase)->startOfDay();
        $termino = Carbon::parse($terminoBase ?? $inicioBase)->endOfDay();

        if ($termino->lessThan($inicio)) {
            Log::warning('Periodo informativo invalido para agenda.', [
                'agendaable_type' => get_class($evento),
                'agendaable_id' => $evento->id ?? null,
                'inicio' => $inicio->toDateTimeString(),
                'termino' => $termino->toDateTimeString(),
            ]);

            return null;
        }

        return [
            'inicio' => $inicio,
            'termino' => $termino,
        ];
    }

    protected static function dadosAgendaInformativa($evento, Carbon $inicio, Carbon $termino): array
    {
        return [
            'titulo' => self::tituloAgendaInformativa($evento),
            'descricao' => self::descricaoAgendaInformativa($evento),
            'inicio' => $inicio,
            'termino' => $termino,
            'dia_todo' => true,
            'local_id' => null,
            'cor_fundo' => '#fef3c7',
            'cor_texto' => '#92400e',
            'atualizado_por' => Auth::id(),
        ];
    }

    protected static function tituloAgendaInformativa($evento): string
    {
        if ($evento instanceof Acao) {
            $codigo = filled($evento->codigo) ? "{$evento->codigo} - " : '';

            return "Acao prevista: {$codigo}{$evento->descricao}";
        }

        return $evento->titulo ?? $evento->descricao ?? 'Evento previsto';
    }

    protected static function descricaoAgendaInformativa($evento): string
    {
        $partes = [
            $evento->descricao ?? null,
        ];

        if ($evento instanceof Acao && filled($evento->local_atividade)) {
            $partes[] = "Local previsto: {$evento->local_atividade}";
        }

        return collect($partes)
            ->filter(fn($parte) => filled($parte))
            ->implode('<br>');
    }

    protected static function normalizarDiaAgendamento($evento, array $dia): ?array
    {
        $data = $dia['data'] ?? (isset($dia['inicio']) ? Carbon::parse($dia['inicio'])->toDateString() : null);

        if (!$data) {
            return null;
        }

        $inicio = $dia['inicio'] ?? self::dataHoraDoDia($data, $dia['hora_inicio'] ?? null);
        $termino = $dia['termino'] ?? self::dataHoraDoDia($data, $dia['hora_termino'] ?? null);

        if (!$inicio || !$termino) {
            return null;
        }

        $id = filled($dia['id'] ?? null) ? (int) $dia['id'] : null;
        $operacao = $dia['operacao'] ?? ($id ? 'update' : 'create');

        return array_merge($dia, [
            'id' => $id,
            'data' => $data,
            'inicio' => Carbon::parse($inicio)->seconds(0)->toDateTimeString(),
            'termino' => Carbon::parse($termino)->seconds(0)->toDateTimeString(),
            'titulo' => $dia['titulo'] ?? 'Curso',
            'descricao' => $dia['descricao'] ?? $evento->titulo,
            'local_id' => $dia['local_id'] ?? $evento->tipolocal_id,
            'atualizado_por' => $dia['atualizado_por'] ?? Auth::id(),
            'operacao' => in_array($operacao, ['create', 'update', 'delete', 'keep'], true) ? $operacao : ($id ? 'update' : 'create'),
        ]);
    }

    protected static function dataHoraDoDia(string $data, ?string $hora): ?string
    {
        if (!$hora) {
            return null;
        }

        return Carbon::parse($data . ' ' . $hora)->seconds(0)->toDateTimeString();
    }

    protected static function dadosAgendaParaDia($evento, array $dia): array
    {
        return [
            'titulo' => $dia['titulo'] ?? 'Curso',
            'descricao' => $dia['descricao'] ?? $evento->titulo,
            'inicio' => $dia['inicio'],
            'termino' => $dia['termino'],
            'local_id' => $dia['local_id'] ?? $evento->tipolocal_id,
            'atualizado_por' => $dia['atualizado_por'] ?? Auth::id(),
        ];
    }

    protected static function buscarAgendaEquivalente(Collection $agendas, array $dados): ?Agenda
    {
        return $agendas->first(function (Agenda $agenda) use ($dados) {
            return (int) $agenda->local_id === (int) $dados['local_id']
                && Carbon::parse($agenda->inicio)->equalTo(Carbon::parse($dados['inicio']))
                && Carbon::parse($agenda->termino)->equalTo(Carbon::parse($dados['termino']));
        });
    }

    protected static function paiIdsIgnorados($evento): array
    {
        if ($evento instanceof CursoAtividades && $evento->curso_id) {
            return [$evento->curso_id];
        }

        return [];
    }
}
