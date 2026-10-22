<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportNetTermUsersSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->mensagem("Buscando dados da Tabela de Usuários do NetTerm");

        $tipo = [
            1 => 2108, //servidor
            2 => 2104, //magistrados
        ];
        $sexo = [
            1 => 2502,
            2 => 2501,
        ];
        $dados = DB::connection('emes')
            ->table('rhpj_cadastro_servidor as rcs')
            ->select('rcs.id', 'rcs.nome_serv', 'rcs.num_cpf_serv', 'rcs.num_mat_serv', 'rcs.dig_mat_serv', 'rcs.cod_cat_serv', 'rcs.cod_sexo_serv')
            // ->where('rcs.num_cpf_serv', '=', '4236972778')
            ->whereIn('rcs.num_mat_serv', function ($query) {
                $query->select('ra.num_mat_aluno')
                    ->from('rhpj_aluno as ra')
                    ->join('rhpj_curso as rc', 'ra.num_curso_aluno', '=', 'rc.num_curso')
                    ->distinct();
            });

        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();
        $dados->orderBy('rcs.id')->chunk(200, function ($dados) use ($progressBar, $tipo, $sexo) {
            DB::beginTransaction();

            try {
                $data = [];
                $sqlPerfil = '';
                foreach ($dados as $row) {
                    $cpf = $this->formatarCpf($row->num_cpf_serv);

                    $data = $this->tratarDados([
                        'nome' => $row->nome_serv,
                        'cpf' => $row->num_cpf_serv,
                        'matricula' => $this->formatarMatricula($row->num_mat_serv, $row->dig_mat_serv),
                    ]);
                    $this->cadastrarUsuario($data);

                    if (isset($tipo[$row->cod_sexo_serv])) {
                        $sexo_id = $sexo[$row->cod_sexo_serv];
                    } else {
                        $sexo_id = null;
                    }

                    if (isset($tipo[$row->cod_cat_serv])) {
                        $tipopessoa_id = $tipo[$row->cod_cat_serv];
                        $chefia = 1;
                    } else {
                        $tipopessoa_id = $tipo[1]; // Default to servidor
                        $chefia = 1;
                    }

                    $sqlPerfil .= "insert into user_perfil(user_id, tipopessoa_id, chefia, sexo) 
                                  values((select id from users where cpf='{$cpf}'), {$tipopessoa_id}, {$chefia}, {$sexo_id});";

                    $progressBar->advance();
                }
                DB::unprepared($sqlPerfil);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->mensagem("Erro ao cadastrar usuário: {$e->getMessage()}");
            }
        });
        $progressBar->finish();
    }
}
