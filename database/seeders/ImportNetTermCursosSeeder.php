<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ImportNetTermCursosSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->mensagem("Buscando dados da Tabela de Cursos do NetTerm");

        $dados = DB::connection('emes')
            ->table('rhpj_curso as rc')
            ->select(
                'rc.num_curso',
                'rc.desc_curso',
                'rc.data_inicio_curso',
                'rc.data_fim_curso',
                'rc.qtde_carga_curso',
                'rc.nome_instrutor_curso',
                'rc.desc_entidade_curso',
                'rc.qtde_vagas_curso',
                'rc.sub_desc_curso'
            )
            // ->where('rc.num_curso', '=', 1)
        ;

        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();
        $dados->orderBy('rc.num_curso')->chunk(200, function ($dados) use ($progressBar) {
            $data = [];
            foreach ($dados as $row) {
                $ano = date('Y', strtotime($row->data_inicio_curso));
                $inicio = date('Y-m-d', strtotime($row->data_inicio_curso));
                $termino = date('Y-m-d', strtotime($row->data_fim_curso));
                $descricao = "<h3>{$row->desc_curso}</h3>
                              <h4>{$row->sub_desc_curso}</h4>
                              <p>Número do curso: {$row->num_curso}<br>
                              Instrutor: {$row->nome_instrutor_curso}<br>
                              Entidade: {$row->desc_entidade_curso}</p>";

                $data[] = [
                    'legado_id' => $row->num_curso,
                    'uuid' => Uuid::uuid4()->toString(),
                    'modalidade_id' => 1601, // 61:presencial 82:EaD
                    'areatematica_id' => 1002, // 41: educação 42:judiciária
                    'realizacao_id' => 1301, // 87: escola 88:externo
                    'tipoatividade_id' => 2902, // 29: curso 28:evento
                    'tipolocal_id' => 3412, // 81: a definir
                    'ano' => $ano,
                    'inicio' => $inicio,
                    'termino' => $termino,
                    'titulo' => $row->desc_curso,
                    'descricao' => $descricao,
                    'carga_horaria' => $row->qtde_carga_curso,
                    'numero_vagas' => $row->qtde_vagas_curso,
                    'ativo' => 0,
                    'atualizado_por' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $progressBar->advance();
            }
            $this->cadastrarCurso($data);
        });
        $progressBar->finish();
    }
}
