<?php

namespace Database\Seeders;

use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ImportNetTermCursoAlunosSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->mensagem("Buscando dados da Tabela de Alunos do NetTerm");

        $dados = DB::connection('emes')
            ->table('rhpj_cadastro_servidor as rcs')
            ->join('rhpj_aluno as ra', 'rcs.num_mat_serv', '=', 'ra.num_mat_aluno')
            ->join('rhpj_curso as rc', 'ra.num_curso_aluno', '=', 'rc.num_curso')
            ->select('ra.id', 'ra.num_curso_aluno', 'ra.num_mat_aluno', 'rcs.num_cpf_serv', 'rc.data_inicio_curso')
            // ->where('rcs.num_cpf_serv', '=', '4236972778')
        ;

        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        $users = User::whereNotNull('matricula')->pluck('id', 'cpf');
        $cursos = Cursos::whereNotNull('legado_id')->pluck('id', 'legado_id');

        $dados->orderBy('ra.id')->chunk(200, function ($dados) use ($progressBar, $cursos, $users) {
            $dataAluno = [];
            $dataFrequencia = [];
            $dataCertificado = [];
            foreach ($dados as $row) {
                $legado_id = $row->num_curso_aluno;
                $cpf = $this->formatarCpf($row->num_cpf_serv);

                if (isset($cursos[$legado_id]) && isset($users[$cpf])) {
                    $cursoId = $cursos[$legado_id];
                    $alunoId = $users[$cpf];

                    $dataAluno[] = [
                        'curso_id' => $cursoId,
                        'uuid' => Uuid::uuid4()->toString(),
                        'aluno_id' => $alunoId,
                        'situacaoinscricao_id' => 2802, // 63: inscrito
                        'data_inscricao' => Carbon::parse($row->data_inicio_curso),
                        'aprovado' => 1,
                        'atualizado_por' => 1,
                        'created_at' => Carbon::parse($row->data_inicio_curso),
                        'updated_at' => Carbon::parse($row->data_inicio_curso),
                    ];
                    $dataFrequencia[] = [
                        'curso_id' => $cursoId,
                        'aluno_id' => $alunoId,
                        'situacaofrequencia_id' => 2701, // 63: inscrito
                        'data_frequencia' => Carbon::parse($row->data_inicio_curso),
                        'atualizado_por' => 1,
                        'created_at' => Carbon::parse($row->data_inicio_curso),
                        'updated_at' => Carbon::parse($row->data_inicio_curso),
                    ];
                    $dataCertificado[] = [
                        'curso_id' => $cursoId,
                        'uuid' => Uuid::uuid4()->toString(),
                        'pessoa_id' => $alunoId,
                        'modelo_id' => 1,
                        'certificable_type' => 'App\Models\Cursos',
                        'certificable_id' => 1, // 1: curso modelo
                        'assinado_por' => 1,
                        'atualizado_por' => 1,
                        'disponivel' => 0,
                        'created_at' => Carbon::parse($row->data_inicio_curso),
                        'updated_at' => Carbon::parse($row->data_inicio_curso),
                    ];
                    $progressBar->advance();
                }
            }
            $this->cadastrarAlunos($dataAluno);
            $this->cadastrarFrequencia($dataFrequencia);
            $this->cadastrarCertificado($dataCertificado);
        });
        $progressBar->finish();
    }
}
