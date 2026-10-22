<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportConfigSeeder extends Seeder
{
    use ImportUtils;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         ** Truncate tables
         */
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('taggables')->truncate();

        DB::table('adm_tarefas')->truncate();
        DB::table('adm_agenda')->truncate();
        DB::table('adm_demandas')->truncate();
        DB::table('adm_registrofuncional')->truncate();
        DB::table('adm_residenciajuridica')->truncate();

        DB::table('cnt_gestores')->truncate();
        DB::table('cnt_processoeventos')->truncate();
        DB::table('cnt_processos')->truncate();
        DB::table('cnt_orcamentoeventos')->truncate();
        DB::table('cnt_orcamento')->truncate();

        DB::table('cad_fornecedores')->truncate();

        DB::table('esc_acaodespesas')->truncate();
        DB::table('esc_acao')->truncate();
        DB::table('esc_comunicacaocurso')->truncate();
        DB::table('esc_comunicacao')->truncate();
        DB::table('esc_cursoalunos')->truncate();
        DB::table('esc_cursoarquivos')->truncate();
        DB::table('esc_cursocertificados')->truncate();
        DB::table('esc_cursocomentarios')->truncate();
        DB::table('esc_cursofrequencias')->truncate();
        DB::table('esc_recursos')->truncate();
        DB::table('esc_cursopublico')->truncate();
        DB::table('esc_instrutores')->truncate();
        DB::table('esc_cursoatividades')->truncate();
        DB::table('esc_cursos')->truncate();
        DB::table('esc_documentos')->truncate();
        DB::table('esc_formsubmissionfields')->truncate();
        DB::table('esc_formsubmissions')->truncate();

        DB::table('lotacao')->truncate();
        DB::table('perfil')->truncate();
        //DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        /*
         ** Usuário Admin
         */
        $dataUser[] = [
            'id' => 1,
            'moodle_id' => 1,
            'login' => 'admin',
            'nome' => 'Administrador',
            'email' => 'admin@emes.jus.br',
            'telefone' => '2733342000',
            'cpf' => '12345678901',
            'password' => Hash::make('admin123'),
            'ativo' => true,
            'avatar' => '01JPQG99MECP20GW9CDHN7RH1H.png',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->cadastrarUsuario($dataUser);

        /*
         ** Curso Base
         */
        $dataCurso[] = [
            'id' => 0,
            'uuid' => '0',
            'titulo' => 'Outros Cursos',
            'aprovacao' => 100,
            'ativo' => false,
            'atualizado_por' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->cadastrarCurso($dataCurso);

    }
}
