<?php

namespace Tests\Feature;

use App\Models\Cadastro\Tabelas;
use App\Models\Escola\CertificadoModelos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\Perfil;
use App\Models\Setores;
use App\Models\User;
use App\Services\Auto\Executor\AbrirInscricaoExecutor;
use App\Services\Auto\Executor\ConfirmarInscricaoExecutor;
use App\Services\Auto\Executor\EncerrarInscricaoExecutor;
use App\Services\Pedagogico\AprovacaoService;
use App\Services\Pedagogico\CertificadoService;
use App\Services\Pedagogico\InscricaoService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PedagogicalFlowTest extends TestCase
{
    private const PRE_INSCRICAO = 2801;
    private const INSCRITO = 2802;
    private const INSCRICAO_CANCELADA = 2803;
    private const FREQUENCIA_PRESENTE = 2701;
    private const FREQUENCIA_AUSENTE = 2702;
    private const LOCAL_A_DEFINIR = 3412;
    private const MODALIDADE_PRESENCIAL = 1601;
    private const TIPO_ATIVIDADE_CURSO = 7001;
    private const PUBLICO_SERVIDOR = 5001;
    private const SEXO_NAO_INFORMADO = 5101;
    private const RACA_NAO_INFORMADA = 5201;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSqliteConnections();
        $this->createSchema();
        $this->configureSettings();
        $this->seedLookupTables();

        Bus::fake();
        Mail::fake();
        Config::set('services.moodle.enabled', false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_fluxo_pedagogico_completo_do_educames(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17 09:00:00'));

        [$admin, $aluno] = $this->createUsersAndProfile();
        $this->actingAs($admin);
        Session::put('setorprincipal', 10);

        $inicioCurso = Carbon::parse('2026-09-21 08:00:00');
        $terminoCurso = Carbon::parse('2026-09-21 12:00:00');

        $curso = Cursos::create([
            'modalidade_id' => self::MODALIDADE_PRESENCIAL,
            'tipoatividade_id' => self::TIPO_ATIVIDADE_CURSO,
            'tipolocal_id' => self::LOCAL_A_DEFINIR,
            'titulo' => 'Curso Funcional Educames',
            'descricao' => 'Curso criado pelo smoke test pedagogico.',
            'conteudo_programatico' => 'Conteudo programatico de teste.',
            'carga_horaria' => 4,
            'numero_vagas' => 10,
            'aprovacao' => 75,
            'certificavel' => true,
            'inicio' => $inicioCurso,
            'termino' => $terminoCurso,
            'inicio_inscricao' => now()->subHour(),
            'termino_inscricao' => now()->addHours(2),
            'inicio_tolerancia' => 15,
            'termino_tolerancia' => 15,
            'liberar_divulgacao' => true,
            'liberar_inscricao' => false,
            'liberar_frequencia' => true,
            'ativo' => true,
        ]);

        $atividade = CursoAtividades::create([
            'curso_id' => $curso->id,
            'modalidade_id' => self::MODALIDADE_PRESENCIAL,
            'tipoatividade_id' => self::TIPO_ATIVIDADE_CURSO,
            'tipolocal_id' => self::LOCAL_A_DEFINIR,
            'titulo' => 'Atividade Funcional Educames',
            'carga_horaria' => 4,
            'aprovacao' => 75,
            'certificavel' => true,
            'inicio' => $inicioCurso,
            'termino' => $terminoCurso,
            'liberar_frequencia' => true,
            'ativo' => true,
        ]);

        $this->assertDatabaseHas('esc_cursos', [
            'id' => $curso->id,
            'titulo' => 'Curso Funcional Educames',
        ]);
        $this->assertDatabaseHas('adm_agenda', [
            'agendaable_id' => $atividade->id,
            'agendaable_type' => CursoAtividades::class,
        ]);

        $autoConfig = [
            'inscricao' => [
                'abertura_automatica' => true,
                'encerramento_automatico' => true,
                'regras' => [
                    'aprovacao_automatica' => true,
                    'criterios' => ['sem-criterio'],
                    'exceder_vagas' => false,
                ],
            ],
        ];

        $abertura = app(AbrirInscricaoExecutor::class)->execute($curso->fresh(), [], $autoConfig);

        $this->assertSame('opened_registration', $abertura['meta']['action']);
        $this->assertTrue($curso->fresh()->liberar_inscricao);

        InscricaoService::preInscrever($curso->fresh('atividades'), collect([$aluno]));

        $this->assertDatabaseHas('esc_cursoalunos', [
            'curso_id' => $curso->id,
            'aluno_id' => $aluno->id,
            'situacaoinscricao_id' => self::PRE_INSCRICAO,
        ]);
        $this->assertDatabaseHas('esc_cursoalunos', [
            'curso_id' => $atividade->id,
            'aluno_id' => $aluno->id,
            'situacaoinscricao_id' => self::PRE_INSCRICAO,
        ]);

        $confirmacao = app(ConfirmarInscricaoExecutor::class)->execute($curso->fresh('atividades'), [], $autoConfig);

        $this->assertSame('confirmed_registrations', $confirmacao['meta']['action']);
        $this->assertSame(1, $confirmacao['meta']['confirmed_count']);
        $this->assertDatabaseHas('esc_cursoalunos', [
            'curso_id' => $curso->id,
            'aluno_id' => $aluno->id,
            'situacaoinscricao_id' => self::INSCRITO,
        ]);

        $frequencia = CursoFrequencias::query()
            ->where('curso_id', $atividade->id)
            ->where('aluno_id', $aluno->id)
            ->first();

        $this->assertNotNull($frequencia, 'A confirmacao da inscricao deve gerar frequencia para a agenda da atividade.');
        $this->assertSame(self::FREQUENCIA_AUSENTE, (int) $frequencia->situacaofrequencia_id);

        Carbon::setTestNow($curso->termino_inscricao->copy()->addMinute());

        $encerramento = app(EncerrarInscricaoExecutor::class)->execute($curso->fresh(), [], $autoConfig);

        $this->assertSame('closed_registration', $encerramento['meta']['action']);
        $this->assertFalse($curso->fresh()->liberar_inscricao);

        $frequencia->presente($admin);

        $this->assertDatabaseHas('esc_cursofrequencias', [
            'id' => $frequencia->id,
            'situacaofrequencia_id' => self::FREQUENCIA_PRESENTE,
        ]);

        $aprovados = AprovacaoService::aprovarAlunos($curso->fresh('atividades'), [$aluno], 'Validacao funcional automatizada.');

        $this->assertGreaterThanOrEqual(1, $aprovados);
        $this->assertDatabaseHas('esc_cursoalunos', [
            'curso_id' => $curso->id,
            'aluno_id' => $aluno->id,
            'aprovado' => true,
        ]);

        $modelo = CertificadoModelos::create([
            'assinado_por' => $admin->id,
            'titulo' => 'Modelo Funcional',
            'arquivo' => null,
            'texto' => 'Certificamos que {NomePessoaCertificada} concluiu {TituloCurso} em {DataCurso}.',
        ]);

        FormAvaliacao::create([
            'name' => 'Avaliacao funcional',
            'auth_required' => false,
            'ativo' => true,
        ]);

        CertificadoService::gerarCertificados([
            'observacao' => 'Certificado gerado pela validacao funcional.',
            'modelo_id' => $modelo->id,
            'assinado_por' => $admin->id,
            'disponivel' => true,
            'totalatvd' => false,
        ], $curso->fresh(['atividades', 'alunos']));

        $certificado = CursoCertificados::query()
            ->where('pessoa_id', $aluno->id)
            ->where('certificable_id', $curso->id)
            ->where('certificable_type', Cursos::class)
            ->first();

        $this->assertNotNull($certificado, 'A geracao de certificados deve criar um certificado para o aluno aprovado.');
        $this->assertTrue((bool) $certificado->disponivel);

        $response = $this->post(route('reports.pedagogico.generate'), [
            'report_type' => 'cursos_aprovados-cargo-setor-simplificado',
            'cursos_ids' => [$curso->id],
        ]);

        $response
            ->assertOk()
            ->assertSee('Curso Funcional Educames')
            ->assertSee($aluno->name)
            ->assertSee('Alunos Aprovados');
    }

    public function test_vinculo_nao_aprova_e_emissao_exige_aprovacao(): void
    {
        [$admin, $aluno] = $this->createUsersAndProfile();
        $this->actingAs($admin);
        Session::put('setorprincipal', 10);

        $curso = Cursos::create([
            'titulo' => 'Curso sem aprovaÃ§Ã£o',
            'aprovacao' => 75,
            'certificavel' => true,
            'inicio' => now(),
            'termino' => now()->addHours(2),
            'ativo' => true,
        ]);

        $this->assertTrue(
            CertificadoService::validarVinculo($curso->id, $aluno->id, false, $curso),
        );

        $this->assertDatabaseHas('esc_cursoalunos', [
            'curso_id' => $curso->id,
            'aluno_id' => $aluno->id,
            'aprovado' => false,
        ]);
        $this->assertFalse(CertificadoService::validarAprovacao($curso->id, $aluno->id));

        $this->expectException(ValidationException::class);

        $curso->certMorph()->create([
            'pessoa_id' => $aluno->id,
            'modelo_id' => null,
        ]);
    }

    private function configureSqliteConnections(): void
    {
        $database = env('DB_DATABASE');

        if (! is_string($database) || $database === '') {
            $database = storage_path('framework/testing-pedagogical.sqlite');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $database);
        Config::set('database.connections.emes', [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::purge('emes');
        DB::reconnect('sqlite');
        DB::reconnect('emes');
    }

    private function configureSettings(): void
    {
        Config::set('settings.inscricao.preinscricao', self::PRE_INSCRICAO);
        Config::set('settings.inscricao.inscrito', self::INSCRITO);
        Config::set('settings.inscricao.cancelada', self::INSCRICAO_CANCELADA);
        Config::set('settings.frequencia.presente', self::FREQUENCIA_PRESENTE);
        Config::set('settings.frequencia.ausente', self::FREQUENCIA_AUSENTE);
        Config::set('settings.modalidade.presencial', self::MODALIDADE_PRESENCIAL);
        Config::set('settings.modalidade.remoto', 1602);
        Config::set('settings.modalidade.hibrido', 1603);
        Config::set('settings.modalidade.ead', 1604);
        Config::set('settings.tipoatividade.curso', self::TIPO_ATIVIDADE_CURSO);
        Config::set('settings.auto.sistemaID', 1);
        Config::set('settings.validacao.validado', 1);
        Config::set('settings.validacao.invalidado', 0);
    }

    private function createSchema(): void
    {
        foreach ([
            'notifications',
            'esc_cursocertificados',
            'esc_cursofrequencias',
            'esc_cursoalunos',
            'adm_agenda',
            'esc_formavaliacao',
            'esc_certificadomodelos',
            'user_lotacao',
            'user_perfil',
            'vw_recadastro',
            'cms_templates',
            'setores',
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'esc_cursopublico',
            'cad_tabelaapoio',
            'esc_cursos',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('login')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('cpf')->nullable()->index();
            $table->string('telefone')->nullable();
            $table->string('moodle_id')->nullable();
            $table->string('matricula')->nullable();
            $table->string('numero_funcional')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('esc_cursos', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->unsignedBigInteger('modalidade_id')->nullable();
            $table->unsignedBigInteger('tipoatividade_id')->nullable();
            $table->unsignedBigInteger('tipoevento_id')->nullable();
            $table->unsignedBigInteger('tipolocal_id')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->integer('ano')->nullable();
            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable();
            $table->text('conteudo_programatico')->nullable();
            $table->decimal('carga_horaria', 8, 2)->nullable();
            $table->integer('numero_vagas')->nullable();
            $table->integer('aprovacao')->nullable();
            $table->boolean('certificavel')->default(false);
            $table->boolean('externo')->default(false);
            $table->dateTime('inicio')->nullable();
            $table->dateTime('termino')->nullable();
            $table->dateTime('inicio_inscricao')->nullable();
            $table->dateTime('termino_inscricao')->nullable();
            $table->string('turno')->nullable();
            $table->string('link')->nullable();
            $table->string('webnar_password')->nullable();
            $table->integer('inicio_tolerancia')->nullable();
            $table->integer('termino_tolerancia')->nullable();
            $table->boolean('liberar_divulgacao')->default(false);
            $table->boolean('liberar_inscricao')->default(false);
            $table->boolean('liberar_frequencia')->default(false);
            $table->boolean('ativo')->default(true);
            $table->json('auto_config')->nullable();
            $table->boolean('auto')->default(false);
            $table->json('auto_steps')->nullable();
            $table->json('auto_status')->nullable();
            $table->timestamps();
        });

        Schema::create('cad_tabelaapoio', function (Blueprint $table): void {
            $table->id();
            $table->string('grupo')->nullable()->index();
            $table->string('codigo')->nullable();
            $table->string('descricao')->nullable();
            $table->json('json')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('esc_cursopublico', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('tipopublico_id');
            $table->timestamps();
        });

        Schema::create('setores', function (Blueprint $table): void {
            $table->id();
            $table->string('setor')->nullable();
            $table->string('unidadeorganizacional')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('setor_id')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->string('name')->nullable();
            $table->string('view')->nullable();
            $table->string('css_path')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('vw_recadastro', function (Blueprint $table): void {
            $table->string('cpf')->primary();
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('tipovinc')->nullable();
            $table->string('cargo')->nullable();
            $table->string('funcao')->nullable();
            $table->string('nomesetor')->nullable();
        });

        Schema::create('user_perfil', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tipopessoa_id')->nullable();
            $table->unsignedBigInteger('sexo')->nullable();
            $table->unsignedBigInteger('raca')->nullable();
            $table->string('cargo')->nullable();
            $table->json('pcd')->nullable();
            $table->timestamps();
        });

        Schema::create('user_lotacao', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('setor_id');
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->boolean('principal')->default(false);
            $table->timestamps();
        });

        Schema::create('esc_certificadomodelos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->unsignedBigInteger('assinado_por')->nullable();
            $table->string('titulo')->nullable();
            $table->string('arquivo')->nullable();
            $table->text('texto')->nullable();
            $table->timestamps();
        });

        Schema::create('esc_formavaliacao', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('auth_required')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('adm_agenda', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('setor_id')->nullable();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable();
            $table->dateTime('inicio')->nullable();
            $table->dateTime('termino')->nullable();
            $table->boolean('dia_todo')->default(false);
            $table->string('cor_fundo')->nullable();
            $table->string('cor_texto')->nullable();
            $table->string('agendaable_type')->nullable();
            $table->unsignedBigInteger('agendaable_id')->nullable();
            $table->unsignedBigInteger('local_id')->nullable();
            $table->timestamps();
        });

        Schema::create('esc_cursoalunos', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('aluno_id');
            $table->unsignedBigInteger('situacaoinscricao_id')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->dateTime('data_inscricao')->nullable();
            $table->text('observacao')->nullable();
            $table->boolean('aprovado')->default(false);
            $table->timestamps();
        });

        Schema::create('esc_cursofrequencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('aluno_id');
            $table->unsignedBigInteger('agenda_id');
            $table->unsignedBigInteger('situacaofrequencia_id')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->dateTime('data_frequencia')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('esc_cursocertificados', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->unsignedBigInteger('pessoa_id');
            $table->decimal('carga_horaria', 8, 2)->nullable();
            $table->unsignedBigInteger('certificable_id')->nullable();
            $table->string('certificable_type')->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->unsignedBigInteger('assinado_por')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->string('titulo')->nullable();
            $table->string('nome')->nullable();
            $table->string('arquivo')->nullable();
            $table->text('conteudo')->nullable();
            $table->boolean('disponivel')->default(false);
            $table->boolean('externo')->default(false);
            $table->integer('validado')->nullable();
            $table->unsignedBigInteger('validado_por')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedLookupTables(): void
    {
        Tabelas::insert([
            [
                'id' => self::PRE_INSCRICAO,
                'grupo' => 'SITUACAO_INSCRICAO',
                'codigo' => 'PRE_INSCRICAO',
                'descricao' => 'Pre-inscricao',
                'json' => null,
            ],
            [
                'id' => self::INSCRITO,
                'grupo' => 'SITUACAO_INSCRICAO',
                'codigo' => 'INSCRITO',
                'descricao' => 'Inscrito',
                'json' => null,
            ],
            [
                'id' => self::INSCRICAO_CANCELADA,
                'grupo' => 'SITUACAO_INSCRICAO',
                'codigo' => 'CANCELADA',
                'descricao' => 'Cancelada',
                'json' => null,
            ],
            [
                'id' => self::FREQUENCIA_PRESENTE,
                'grupo' => 'SITUACAO_FREQUENCIA',
                'codigo' => 'PRESENTE',
                'descricao' => 'Presente',
                'json' => null,
            ],
            [
                'id' => self::FREQUENCIA_AUSENTE,
                'grupo' => 'SITUACAO_FREQUENCIA',
                'codigo' => 'AUSENTE',
                'descricao' => 'Ausente',
                'json' => null,
            ],
            [
                'id' => self::LOCAL_A_DEFINIR,
                'grupo' => 'TIPO_LOCAL',
                'codigo' => 'LOCAL_A_DEFINIR',
                'descricao' => 'Local a definir',
                'json' => json_encode(['capacidade' => 50]),
            ],
            [
                'id' => self::MODALIDADE_PRESENCIAL,
                'grupo' => 'MODALIDADE',
                'codigo' => 'PRESENCIAL',
                'descricao' => 'Presencial',
                'json' => null,
            ],
            [
                'id' => self::TIPO_ATIVIDADE_CURSO,
                'grupo' => 'ATIVIDADE_EDUCACIONAL',
                'codigo' => 'CURSO',
                'descricao' => 'Curso',
                'json' => null,
            ],
            [
                'id' => self::PUBLICO_SERVIDOR,
                'grupo' => 'PUBLICO_ALVO',
                'codigo' => 'SERVIDOR',
                'descricao' => 'Servidor',
                'json' => null,
            ],
            [
                'id' => self::SEXO_NAO_INFORMADO,
                'grupo' => 'SEXO',
                'codigo' => 'NI',
                'descricao' => 'Nao informado',
                'json' => null,
            ],
            [
                'id' => self::RACA_NAO_INFORMADA,
                'grupo' => 'RACA_COR',
                'codigo' => 'NI',
                'descricao' => 'Nao informada',
                'json' => null,
            ],
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function createUsersAndProfile(): array
    {
        $admin = User::withoutEvents(fn (): User => User::create([
            'name' => 'Administrador Funcional',
            'email' => 'admin.funcional@example.test',
            'password' => bcrypt('password'),
            'cpf' => '11111111111',
        ]));

        $aluno = User::withoutEvents(fn (): User => User::create([
            'name' => 'Aluno Funcional Educames',
            'email' => 'aluno.funcional@example.test',
            'password' => bcrypt('password'),
            'cpf' => '22222222222',
        ]));

        Auth::login($admin);

        Setores::create([
            'id' => 10,
            'setor' => 'Setor Pedagogico Funcional',
            'unidadeorganizacional' => 'EMES',
        ]);

        DB::table('cms_templates')->insert([
            'setor_id' => 10,
            'atualizado_por' => $admin->id,
            'name' => 'Template funcional',
            'view' => 'templates.default',
            'css_path' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_lotacao')->insert([
            [
                'user_id' => $admin->id,
                'setor_id' => 10,
                'principal' => true,
                'atualizado_por' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $aluno->id,
                'setor_id' => 10,
                'principal' => true,
                'atualizado_por' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Perfil::create([
            'user_id' => $aluno->id,
            'tipopessoa_id' => self::PUBLICO_SERVIDOR,
            'sexo' => self::SEXO_NAO_INFORMADO,
            'raca' => self::RACA_NAO_INFORMADA,
            'cargo' => 'Analista Judiciario',
            'pcd' => [],
        ]);

        DB::table('vw_recadastro')->insert([
            'cpf' => $aluno->cpf,
            'nome' => $aluno->name,
            'email' => $aluno->email,
            'tipovinc' => 'EFETIVO',
            'cargo' => 'Analista Judiciario',
            'funcao' => null,
            'nomesetor' => 'Setor Pedagogico Funcional',
        ]);

        return [$admin, $aluno];
    }
}
