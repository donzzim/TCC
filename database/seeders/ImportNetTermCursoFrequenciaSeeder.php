<?php

namespace Database\Seeders;

use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ImportNetTermCursoFrequenciaSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->mensagem("Buscando dados da Tabela de Frequencia do NetTerm");

        $dados = DB::connection('emes')
            ->table('rhpj_cadastro_servidor as rcs')
            ->join('rhpj_aluno as ra', 'rcs.num_mat_serv', '=', 'ra.num_mat_aluno')
            ->join('rhpj_curso as rc', 'ra.num_curso_aluno', '=', 'rc.num_curso')
            ->select('ra.id', 'ra.num_curso_aluno', 'ra.num_mat_aluno', 'rcs.num_cpf_serv', 'rcs.data_inicio_curso')
            ->where('rcs.num_cpf_serv', '=', '4236972778')
        ;

        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        $users = User::whereNotNull('matricula')->pluck('id', 'cpf');
        $cursos = Cursos::whereNotNull('legado_id')->pluck('id', 'legado_id');

        $dados->orderBy('ra.id')->chunk(200, function ($dados) use ($progressBar, $cursos, $users) {
            $data = [];
            foreach ($dados as $row) {
                $legado_id = $row->num_curso_aluno;
                $cpf = $row->num_cpf_serv;

                if (isset($cursos[$legado_id]) && isset($users[$cpf])) {
                    $cursoId = $cursos[$legado_id];
                    $alunoId = $users[$cpf];

                    $atv = CursoAtividades::where('curso_id', $cursoId)->first();

                    $data[] = [
                        'curso_id' => $cursoId,
                        'cursoatividade_id' => $atv->id,
                        'aluno_id' => $alunoId,
                        'situacaofrequencia_id' => 65, // 65: presente
                        'data_frequencia' => $atv->inicio,
                        'atualizado_por' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $progressBar->advance();
                }
            }
            $this->cadastrarFrequencia($data);
        });
        $progressBar->finish();
    }
}
