<?php

namespace Database\Seeders;

use App\Models\Cadastro\Tabelas;
use App\Models\Escola\Acao;
use App\Models\Escola\AreaTematica;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoPublico;
use App\Models\Escola\Cursos;
use App\Models\Escola\Modalidade;
use App\Models\Escola\TipoAtividade;
use App\Models\Escola\TipoEvento;
use App\Models\Escola\TipoLocal;
use App\Models\Escola\TipoPublico;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class ImportEmeronCursosSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */

    public function cadastrarPublico($row, $curso_id, $publico)
    {

        if ($row->idFormacaoPublico == 4) {
            $publicoNew = CursoPublico::updateOrCreate(['curso_id' => $curso_id], [
                'curso_id' => $curso_id,
                'tipopublico_id' => 2104,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $publicoNew = CursoPublico::updateOrCreate(['curso_id' => $curso_id], [
                'curso_id' => $curso_id,
                'tipopublico_id' => 2105,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $publico_id = isset($publico[$row->idFormacaoPublico]) ? $publico[$row->idFormacaoPublico] : 2101;
            $publicoNew = CursoPublico::updateOrCreate(['curso_id' => $curso_id], [
                'curso_id' => $curso_id,
                'tipopublico_id' => $publico_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    public function run(): void
    {
        /*
         ** EmeronWeb
         */
        Auth::loginUsingId(1, true);

        /*
         ** Ação Pedagógica
         */
        $this->mensagem("Buscando dados da Tabela de Ações Pedagógicas do EmeronWeb");
        $dados = DB::connection('emeron')
            ->table('orcevento as o')
            ->select([
                'o.numEvento',
                'o.nome',
                'o.processo',
                DB::raw('IFNULL(TRIM(o.ch),0) as ch'),
                DB::raw('IFNULL(TRIM(o.vagas),0) as vagas'),
                'o.criterioSelecao',
                'o.percentualAprovacao',
                'o.obs',
                'o.dataInicio',
                'o.dataFim',
                'o.infoPreliminar',
                'o.ementa',
                'o.objetivo',
                'o.situacao',
            ]);
        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();
        $dados->orderBy('o.numEvento')->chunk(20, function ($dados) use ($progressBar) {
            DB::beginTransaction();

            try {

                foreach ($dados as $row) {
                    $inicio = Carbon::parse($row->dataInicio);
                    $termino = Carbon::parse($row->dataFim);
                    $ano = $inicio->year;
                    $situacao = ($row->situacao == 0 ? 'Inativo' : 'Ativo');
                    $descricao = null;
                    if ($row->nome)
                        $descricao .= "<h3>{$row->nome} <b>{$situacao}</b></h3>";
                    if ($row->processo)
                        $descricao .= "<p>Processo: {$row->processo}</p>";
                    if ($row->percentualAprovacao)
                        $descricao .= "<p>Percentual Aprovação: {$row->percentualAprovacao}</p>";

                    $data = [
                        'codigo' => $row->numEvento,
                        'ano' => $ano,
                        'descricao' => $descricao,
                        'data_inicio' => $inicio,
                        'data_termino' => $termino,
                        'carga_horaria' => $row->ch ?? 0,
                        'numero_vagas' => $row->vagas ?? 0,
                        'informacao' => $row->infoPreliminar,
                        'objetivo' => $row->objetivo,
                        'ementa' => $row->ementa,
                        'criterio_selecao' => $row->criterioSelecao,
                        'observacao' => $row->obs,
                        'atualizado_por' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    Acao::create($data);

                    $progressBar->advance();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->mensagem("Erro ao cadastrar ação pedagógica:  {$e->getMessage()}");
                return;
            }
        });
        $progressBar->finish();

        /*
         ** Cursos e Eventos
         */
        $publico = TipoPublico::select('id', 'json')->get()->pluck('id', 'json.emeron_id'); // publico alvo
        $local = TipoLocal::select('id', 'json')->get()->pluck('id', 'json.emeron_id'); // salas
        $tipoatividade = Tabelas::select('id', 'json')->where('grupo', '=', 'TIPO_ATIVIDADE')->get()->pluck('id', 'json.emeron_id'); // Ensino, Pesquisa, Extensão
        $tipoformacao = Tabelas::select('id', 'json')->where('grupo', '=', 'TIPO_FORMACAO')->get()->pluck('id', 'json.emeron_id'); // curso e evento
        $subtipo = TipoEvento::select('id', 'json')->get()->pluck('id', 'json.emeron_id'); // congresso, seminário, etc.
        $area = AreaTematica::select('id', 'json')->get()->pluck('id', 'json.emeron_id'); // ADM, JUD, EDU
        $modalidade = Modalidade::select('id', 'json')->get()->pluck('id', 'json.emeron_id'); // presencial, EAD, híbrido
        $realizacao = TipoEvento::select('id', 'json')->get()->pluck('id', 'json.emeron_id'); // escola e externo
        $acao = Acao::pluck('id', 'codigo'); // acao pedagógica

        $this->mensagem("Buscando dados da Tabela de Cursos do EmeronWeb");
        $dados = DB::connection('emeron')
            ->table('formacaoprojeto as fp')
            ->join('formacaoarea as fa', 'fa.idFormacaoArea', '=', 'fp.idFormacaoArea')
            ->join('formacaopublico as fpu', 'fpu.idFormacaoPublico', '=', 'fp.idFormacaoPublico')
            ->select([
                'fp.idFormacaoProjeto',
                'fp.ano',
                'fp.nome',
                'fp.protocolo',
                DB::raw('IFNULL(TRIM(fp.ch),0) as ch'),
                DB::raw('IFNULL(TRIM(fp.vagas),0) as vagas'),
                'fp.obs',
                'fp.dataInicio',
                'fp.dataFim',
                'fa.idFormacaoArea',
                'fa.deFormacaoArea',
                'fpu.idFormacaoPublico',
                'fpu.deFormacaoPublico',
            ]);
        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();
        $dados->orderBy('fp.idFormacaoProjeto')->chunk(20, function ($dados) use ($progressBar, $acao, $publico, $local, $area, $modalidade, $realizacao, $tipoatividade, $tipoformacao, $subtipo) {
            DB::beginTransaction();

            try {

                foreach ($dados as $row) {
                    $legado_id = "E{$row->idFormacaoProjeto}";
                    $ano = $row->ano;
                    $inicio = Carbon::parse($row->dataInicio);
                    $termino = Carbon::parse($row->dataFim);
                    $ch = 0;
                    $vagas = 0;
                    if (trim($row->ch))
                        $ch = trim($row->ch);
                    if (trim($row->vagas))
                        $vagas = trim($row->vagas);

                    $descricao = null;
                    if ($row->nome)
                        $descricao .= "<h3>{$row->nome}</h3>";
                    if ($row->protocolo)
                        $descricao .= "<p>Protocolo: {$row->protocolo}</p>";
                    if ($row->obs)
                        $descricao .= "<p>Observação: {$row->obs}</p>";

                    $area_id = isset($area[$row->idFormacaoArea]) ? $area[$row->idFormacaoArea] : null;
                    $publico = isset($publico[$row->idFormacaoPublico]) ? $area[$row->idFormacaoPublico] : null;

                    $data = [
                        'legado_id' => $legado_id,
                        'uuid' => Uuid::uuid4()->toString(),
                        'areatematica_id' => $area_id,
                        'ano' => $ano,
                        'inicio' => $inicio,
                        'termino' => $termino,
                        'titulo' => $row->nome,
                        'descricao' => $descricao,
                        'carga_horaria' => $ch,
                        'numero_vagas' => $vagas,
                        'ativo' => 0,
                        'atualizado_por' => Auth::id(),
                        'created_at' => $inicio,
                        'updated_at' => $inicio,
                    ];
                    // $cursopai_id = Cursos::create($data)->id;
                    $cursopai_id = Cursos::updateOrCreate(['legado_id' => $legado_id], $data)->id;

                    if ($publico)
                        $this->cadastrarPublico($row, $cursopai_id, $publico);

                    $this->mensagem("Buscando dados da Tabela de Cursos Filhos do EmeronWeb");
                    $dadosCursos = DB::connection('emeron')
                        ->table('formacao as f')
                        ->join('orcevento as o', 'o.idOrcEvento', '=', 'f.idOrcEvento')
                        ->join('inscricao as i', 'f.idFormacao', '=', 'i.idFormacao')
                        ->select([
                            'f.idFormacao',
                            'o.nome',
                            'o.processo',
                            DB::raw('IFNULL(TRIM(o.ch),0) as ch'),
                            DB::raw('IFNULL(TRIM(o.vagas),0) as vagas'),
                            'f.inscricao',
                            'f.inscricaoInicio',
                            'f.inscricaoFim',
                            'o.numEvento',
                            'o.criterioSelecao',
                            'o.dataInicio',
                            'o.dataFim',
                            'f.obs',
                            'o.idFormacaoRealizacao',
                            'o.idFormacaoModalidade',
                            'o.idFormacaoPublico',
                            'o.idFormacaoArea',
                            'o.idFormacaoLocal',
                            'o.idFormacaoTipo',
                            'o.idFormacaoSubtipo',
                            'o.idFormacaoAtividade',
                            'o.percentualAprovacao',
                            'i.dataInicial',
                            'i.dataFinal',
                            'i.descricao as descricaoInscricao',
                            'i.msgDestaque',
                            'i.qtdeParticipantes',
                        ])
                        ->where('idFormacaoProjeto', '=', $row->idFormacaoProjeto)
                        ->get();

                    foreach ($dadosCursos as $curso) {
                        $legado_id = "EF{$curso->idFormacao}";
                        $inicio = Carbon::parse($curso->dataInicio);
                        $ano = $inicio->year;
                        $termino = Carbon::parse($curso->dataFim);
                        $inicioInscricao = Carbon::parse($curso->dataInicial);
                        $terminoInscricao = Carbon::parse($curso->dataFinal);
                        $turno = $this->getTurnoPeloData($inicio);
                        $ch = 0;
                        $vagas = 0;
                        if (trim($curso->ch))
                            $ch = $curso->ch;
                        if (trim($curso->vagas))
                            $vagas = $curso->vagas;

                        $descricao = null;
                        if ($curso->nome)
                            $descricao .= "<h3>{$curso->nome}</h3>";
                        if ($curso->numEvento)
                            $descricao .= "<p>Num. Evento: {$curso->numEvento}</p>";
                        if ($curso->criterioSelecao)
                            $descricao .= "<p>Critério de Seleção: {$curso->criterioSelecao}</p>";
                        if ($curso->processo)
                            $descricao .= "<p>Processo: {$curso->processo}</p>";
                        if ($curso->obs)
                            $descricao .= "<p>Observação: {$curso->obs}</p>";
                        if ($curso->descricaoInscricao)
                            $descricao .= "<p>Descrição da Inscrição: {$curso->descricaoInscricao}</p>";
                        if ($curso->msgDestaque)
                            $descricao .= "<p>Mensagem Destaque da Inscrição: {$curso->msgDestaque}</p>";
                        if ($curso->qtdeParticipantes)
                            $descricao .= "<p>Qtde Participantes: {$curso->qtdeParticipantes}</p>";


                        $acao_id = isset($acao[$curso->numEvento]) ? $acao[$curso->numEvento] : null;
                        $tipoformacao_id = isset($tipoformacao[$curso->idFormacaoTipo]) ? $tipoformacao[$curso->idFormacaoTipo] : null;
                        $tipoatividade_id = isset($tipoatividade[$curso->idFormacaoTipo]) ? $tipoatividade[$curso->idFormacaoTipo] : null;
                        $area_id = isset($area[$curso->idFormacaoArea]) ? $area[$curso->idFormacaoArea] : null;
                        $local_id = isset($local[$curso->idFormacaoLocal]) ? $local[$curso->idFormacaoLocal] : null;
                        $publico = isset($publico[$curso->idFormacaoPublico]) ? $area[$curso->idFormacaoPublico] : null;
                        $modalidade_id = isset($modalidade[$curso->idFormacaoModalidade]) ? $modalidade[$curso->idFormacaoModalidade] : null;
                        $realizacao_id = isset($realizacao[$curso->idFormacaoRealizacao]) ? $realizacao[$curso->idFormacaoRealizacao] : null;
                        $subtipo_id = isset($subtipo[$curso->idFormacaoSubtipo]) ? $subtipo[$curso->idFormacaoSubtipo] : null;

                        $dataCurso = [
                            'acao_id' => $acao_id,
                            'curso_id' => $cursopai_id,
                            'legado_id' => $legado_id,
                            'uuid' => Uuid::uuid4()->toString(),
                            'tipoformacao_id' => $tipoformacao_id,
                            'tipoatividade_id' => $tipoatividade_id,
                            'tipoevento_id' => $subtipo_id,
                            'modalidade_id' => $modalidade_id,
                            'areatematica_id' => $area_id,
                            'realizacao_id' => $realizacao_id,
                            'tipolocal_id' => $local_id,
                            'ano' => $ano,
                            'inicio' => $inicio,
                            'termino' => $termino,
                            'titulo' => $curso->nome,
                            'descricao' => $descricao,
                            'carga_horaria' => $ch,
                            'numero_vagas' => $vagas,
                            'aprovacao' => $curso->percentualAprovacao ?? 100,
                            'criterio_selecao' => $curso->criterioSelecao,
                            'ativo' => 0,
                            'inicio_inscricao' => $inicioInscricao,
                            'termino_inscricao' => $terminoInscricao,
                            'turno' => $turno,
                            'atualizado_por' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // $curso_id = Cursos::create($data)->id;
                        $curso_id = Cursos::updateOrCreate(['legado_id' => $legado_id], $dataCurso)->id;

                        if ($publico)
                            $this->cadastrarPublico($curso, $curso_id, $publico);

                        $progressBar->advance();
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                dd($e);
                DB::rollback();
                $this->mensagem("Erro ao cadastrar curso: {$e->getMessage()}");
                return;
            }
        });
        $progressBar->finish();
    }
}
