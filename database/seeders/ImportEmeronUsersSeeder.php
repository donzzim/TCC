<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportEmeronUsersSeeder extends Seeder
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
        $tipo = [
            1 => [2102, 0], // estagiário(a)
            2 => [2104, 1], // magistrado(a)
            3 => [2101, 0], // Todos/Aberto
            4 => [2108, 0], // Servidor(a)
            5 => [2107, 0], // Residente Jurídico
            6 => [2103, 0], // Juiz Leigo
            7 => [2204, 0], // assessor(a)
        ];

        $this->mensagem("Buscando dados da Tabela de Usuários do EmeronWeb");
        $subquery = DB::connection('emeron')
            ->table('inscricaoaluno as ia')
            ->select('ia.idPessoa', DB::raw('MAX(idInscricaoAluno) as idInscricaoAluno'))
            ->groupBy('ia.idPessoa');

        $dados = DB::connection('emeron')
            ->table('pessoa as p')
            ->leftJoin('pessoadeficiencia as p2', 'p.idPessoa', '=', 'p2.idPessoa')
            ->leftJoin('deficiencia as d', 'p2.idDeficiencia', '=', 'd.idDeficiencia')
            ->join('inscricaoaluno as i', 'p.idPessoa', '=', 'i.idPessoa')
            ->join('pessoatipo as pt', 'i.idPessoaTipo', '=', 'pt.idPessoaTipo')
            // ->join(DB::raw('(select max(t.idInscricaoAluno) idInscricaoAluno, t.idPessoa from inscricaoaluno as t group by t.idPessoa) as ia'), 'ia.idInscricaoAluno', 'i.idInscricaoAluno')
            ->joinSub($subquery, 'ia', function ($join) {
                $join->on('ia.idInscricaoAluno', '=', 'i.idInscricaoAluno');
            })
            ->select(
                'p.idPessoa as cpf',
                'p.nome',
                'p.email01 as email',
                'p.celular01 as telefone',
                'p.sexo',
                'i.idPessoaTipo'
            )
            ->orderBy('p.idPessoa')
            // ->where('p.idPessoa', '=', '04236972778')
        ;

        $total = $dados->count();
        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        $dados->orderBy('p.idPessoa')->chunk(200, function ($dados) use ($progressBar, $tipo) {
            DB::beginTransaction();

            try {
                $data = [];
                $sqlPerfil = '';

                foreach ($dados as $row) {
                    $cpf = $this->formatarCpf($row->cpf);
                    $data = $this->tratarDados([
                        'cpf' => $row->cpf,
                        'nome' => $row->nome,
                        'email' => $row->email,
                        'telefone' => $row->telefone,
                    ]);

                    $user_id = User::updateOrCreate([
                        'cpf' => $cpf,
                    ], $data)->id;

                    ($row->sexo == 'F') ? $sexo = 2502 : $sexo = 2501;
                    if (isset($tipo[$row->idPessoaTipo])) {
                        $tipopessoa_id = $tipo[$row->idPessoaTipo];
                        $chefia = 1;
                    } else {
                        $tipopessoa_id = $tipo[1]; // Default to servidor
                        $chefia = 1;
                    }

                    $dataPerfil = [
                        'sexo' => $sexo,
                        'tipopessoa_id' => $tipopessoa_id,
                        'chefia' => $chefia,
                    ];

                    Perfil::updateOrCreate([
                        'user_id' => $user_id,
                    ], $dataPerfil);

                    $progressBar->advance();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->mensagem("Erro ao cadastrar usuário: {$e->getMessage()}");
            }
        });
        $progressBar->finish();
    }
}
