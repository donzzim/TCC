<?php

namespace Database\Seeders;

use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ImportNetTermCursoCertificadosSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->mensagem("Buscando dados da Tabela de Certificados do NetTerm");

        $dados = DB::connection('emes')
            ->table('rhpj_cadastro_servidor as rcs')
            ->join('rhpj_aluno as ra', 'rcs.num_mat_serv', '=', 'ra.num_mat_aluno')
            ->select('ra.id', 'ra.num_curso_aluno', 'ra.num_mat_aluno', 'rcs.num_cpf_serv')
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

                    // $atv = CursoAtividades::where('curso_id', $cursoId)->first();

                    $data[] = [
                        'curso_id' => $cursoId,
                        // 'cursoatividade_id' => $atv->id,
                        'uuid' => Uuid::uuid4()->toString(),
                        'pessoa_id' => $alunoId,
                        'modelo_id' => 1,
                        'certificable_type' => 'App\Models\Cursos',
                        'certificable_id' => 1, // 1: curso modelo
                        'assinado_por' => 1,
                        'atualizado_por' => 1,
                        'disponivel' => 0,
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
