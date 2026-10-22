<?php

namespace Database\Seeders;

use App\Models\Cadastro\Tabelas;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\Perfil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ImportEmeronCursoAlunosSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         ** EmeronWeb
         */
        Auth::loginUsingId(1, true);

        $tipopessoa = Tabelas::select('id', 'json')->where('grupo', '=', 'TIPO_PESSOA')->get()->pluck('id', 'json.emeron_id'); // curso e evento        

        $this->mensagem("Buscando dados da Tabela de Alunos do EmeronWeb");
        $dados = DB::connection('emeron')
            ->table('formacaoprojeto as fp')
            ->join('formacao as f', 'fp.idFormacaoProjeto', '=', 'f.idFormacaoProjeto')
            ->join('formacaoaluno as fa', 'f.idFormacao', '=', 'f.idFormacao')
            ->join('pessoa as pe', 'fa.idPessoa', 'pe.idPessoa')
            ->leftJoin('pessoadeficiencia as pd', 'pe.idPessoa', 'pd.idPessoa')
            ->leftJoin('deficiencia as de', 'pe.idDeficiencia', 'de.idDeficiencia')
            ->select([
                'fp.idFormacaoProjeto',
                'f.idFormacao',
                'fp.nome as nomeprojeto',
                'fa.idPessoa as cpf',
                'fa.concluinte',
                'fa.obs',
                'pe.nome as nomepessoa',
                'pe.sexo',
                'pe.email01 as email',
                'pe.celular01 as telefone',
                'pe.dataNascimento',
                'pe.nomeSocial',
                'de.idDeficiencia',
                'de.deficiencia',
                'de.necessidade',
            ]);
        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();
        $dados->orderBy('f.idFormacaoProjeto')->chunk(200, function ($dados) use ($progressBar, $tipopessoa) {
            DB::beginTransaction();
            try {

                foreach ($dados as $row) {
                    $diretor = User::where('cpf', '=', '83616527791')->first(); // diretor
                    $tipopessoa_id = isset($tipopessoa[$row->idPessoaTipo]) ? $tipopessoa[$row->idPessoaTipo] : null;
                    $aluno_id = User::where('cpf', '=', $row->cpf)->id;

                    ($row->concluinte == 'S') ? $aprovado = 1 : $aprovado = 0;

                    // $legado_id = "E{$row->idFormacaoProjeto}";
                    // $curso = Cursos::where('legado_id', '=', $row->idFormacaoProjeto)->first(); // curso
                    // $dataAluno[] = [
                    //     'curso_id' => $curso->id,
                    //     'uuid' => Uuid::uuid4()->toString(),
                    //     'aluno_id' => $aluno_id,
                    //     'situacaoinscricao_id' => 2802, // inscrito
                    //     'data_inscricao' => Carbon::parse($row->data_inicio_curso),
                    //     'aprovado' => 1,
                    //     'atualizado_por' => Auth::id(),
                    //     'created_at' => Carbon::parse($row->data_inicio_curso),
                    //     'updated_at' => Carbon::parse($row->data_inicio_curso),
                    // ];

                    $legado_id = "EF{$row->idFormacao}";
                    $curso = Cursos::where('legado_id', '=', $legado_id)->first(); // curso
                    $dataAluno[] = [
                        'curso_id' => $curso->id,
                        'uuid' => Uuid::uuid4()->toString(),
                        'aluno_id' => $aluno_id,
                        'situacaoinscricao_id' => 2802, // inscrito
                        'data_inscricao' => Carbon::parse($row->data_inicio_curso),
                        'aprovado' => 1,
                        'atualizado_por' => Auth::id(),
                        'created_at' => Carbon::parse($row->data_inicio_curso),
                        'updated_at' => Carbon::parse($row->data_inicio_curso),
                    ];
                    Cursos::updateOrCreate(['legado_id' => $legado_id], $dataAluno);

                    if ($row->idDeficiencia == 7) {
                        $pcd = 1;
                        $deficiencia_id = 3010;
                    } else if ($row->idDeficiencia == 8) {
                        $pcd = 1;
                        $deficiencia_id = 3011;
                    } else {
                        $pcd = 0;
                        $deficiencia_id = null;
                    }

                    if ($row->sexo == 'M') {
                        $sexo = 2501;
                    } else if ($row->sexo == 'F') {
                        $sexo = 2502;
                    } else {
                        $sexo = 2504;
                    }

                    if ($row->concluinte == 'S') {
                        $situacaoinscricao_id = 2702;
                        $aprovado = 1;
                    } else {
                        $situacaoinscricao_id = 2701;
                        $aprovado = 0;
                    }

                    $dataPerfil = [
                        'user_id' => $aluno_id,
                        'tipopessoa_id' => $tipopessoa_id,
                        'pcd' => $pcd,
                        'pcd_necessidade' => $deficiencia_id,
                        // 'raca' => $aluno_id,
                        'sexo' => $sexo,
                        // 'chefia' => $aluno_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    Perfil::updateOrCreate(['user_id' => $aluno_id], $dataPerfil);


                    if ($aprovado) {
                        $dataCertificado = [
                            'uuid' => Uuid::uuid4()->toString(),
                            'curso_id' => $curso->id,
                            'pessoa_id' => $aluno_id,
                            'disponivel' => 1, // somente para os aprovados
                            'certificable_type' => 'App\Models\User',
                            'certificable_id' => $aluno_id,
                            'modelo_id' => 1, //assinado pelo diretor
                            'assinado_por' => $diretor->id,
                            'atualizado_por' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        CursoCertificados::updateOrCreate([
                            'curso_id' => $curso->inicio,
                            'pessoa_id' => $aluno_id,
                        ], $dataCertificado);
                    }

                    $progressBar->advance();
                }
                DB::commit();
            } catch (\Exception $e) {
                dd($e);
                DB::rollback();
                $this->mensagem("Erro ao cadastrar alunos:  {$e->getMessage()}");
                return;
            }
        });
        $progressBar->finish();
    }
}
