<?php

namespace App\Services\Pedagogico;



use App\Models\Escola\Comendas;
use App\Models\Escola\Mencoes;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\CursoAtividades;
use App\Models\User;
use App\Models\Escola\Cursos;
use App\Services\EmailService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificadoService
{

    private static function actorId(): int
    {
        return Auth::id() ?? config('settings.auto.sistemaID') ?? 1;
    }



    public static function validarVinculo(
        int $curso_id,
        int $user_id,
        bool $aprovar = false,
        Cursos|CursoAtividades|null $evento = null,
    ): bool
    {
        try {

            $user = User::find($user_id);
            $curso = $evento ?? Cursos::find($curso_id) ?? CursoAtividades::find($curso_id);

            if (! $user || ! $curso) {
                Log::warning('CERTIFICADO SERVICE - validarVinculo(): usuario ou evento nao encontrado.', [
                    'curso_id' => $curso_id,
                    'user_id' => $user_id,
                ]);

                return false;
            }

            $vinculo = CursoAlunos::where('curso_id', $curso_id)
                ->where('aluno_id', $user_id)
                ->exists();

            if (!$vinculo) {
                $user = User::find($user_id);
                $curso = $evento ?? Cursos::find($curso_id) ?? CursoAtividades::find($curso_id);
                Log::info('CERTIFICADO SERVICE - validarVinculo(): Usuário ' . $user_id . ' não possui vínculo com o curso ' . $curso->id);
                InscricaoService::inscrever($curso, [$user], false, null, false);
            }



            $aluno = CursoAlunos::where('curso_id', $curso_id)
                ->where('aluno_id', $user_id)
                ->first();
            if ($aluno) {

                return ! $aprovar || self::validarAprovacao($curso_id, $user_id);
            } else {
                Log::info('CERTIFICADO SERVICE - validarVinculo(): Vínculo do usuário ' . $user_id . ' com o curso ' . $curso_id . ' não encontrado após inscrição.');
                return false;
            }
        } catch (Exception $e) {
            Log::error('CERTIFICADO SERVICE - validarVinculo(): Erro ao validar vínculo do usuário ' . $user_id . ' com o curso ' . $curso_id . '. Erro: ' . $e->getMessage());
            return false;
        }
    }


    public static function validarAprovacao(int $curso_id, int $user_id): bool
    {
        try {
            $aluno = CursoAlunos::query()
                ->where('curso_id', $curso_id)
                ->where('aluno_id', $user_id)
                ->first();

            if ($aluno?->aprovado) {
                return true;
            }

            Log::info('CERTIFICADO SERVICE - validarAprovacao(): usuario nao esta aprovado.', [
                'curso_id' => $curso_id,
                'user_id' => $user_id,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('CERTIFICADO SERVICE - validarAprovacao(): erro ao validar aprovacao.', [
                'curso_id' => $curso_id,
                'user_id' => $user_id,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function validarCertificados(bool $validado, array $certificados): bool
    {
        try {
            $validadorId = self::actorId();
            $validacao = $validado ? 'VALIDAÇÃO' : 'INVALIDAÇÃO';
            Log::info('CERTIFICADO SERVICE - validarCertificado(): Iniciando ' . $validacao . ' de ' . count($certificados) . ' certificados pelo usuario ' . $validadorId);
            if ($validado) {
                foreach ($certificados as $certificado) {
                    Log::info('CERTIFICADO SERVICE - validarCertificado(): Validando certificado externo de ID ' . $certificado->id . ' para o usuário ' . $certificado->pessoa_id);
                    $certificado->update([
                        'validado' => config('settings.validacao.validado'),
                        'validado_por' => $validadorId,
                        'validated_at' => now(),
                    ]);
                }
            } else {
                foreach ($certificados as $certificado) {
                    Log::info('CERTIFICADO SERVICE - validarCertificado(): Invalidando certificado externo de ID ' . $certificado->id . ' para o usuário ' . $certificado->pessoa_id);
                    $certificado->update([
                        'validado' => config('settings.validacao.invalidado'),
                        'validado_por' => $validadorId,
                        'validated_at' => now(),
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            Log::error('CERTIFICADO SERVICE - validarCertificado(): Erro ao validar certificado. Erro: ' . $e->getMessage());
            return false;
        }
    }


    public static function gerarComenda($data)
    {
        $modelo = $data['modelo_id'];

        $aluno_id = $data['aluno_id'];

        $nome = $data['nome'];

        $assinado_por = $data['assinado_por'];

        $observacao = $data['observacao'];

        $disponivel = $data['disponivel'];

        try {
            DB::beginTransaction();

            if ($modelo == 7) {


                Comendas::create(
                    [
                        'pessoa_id' => $aluno_id,
                        'modelo_id' => $modelo,
                        'certificable_type' => Comendas::class,
                        'certificable_id' => null,
                        'assinado_por' => $assinado_por,
                        'atualizado_por' => self::actorId(),
                        'nome' => $nome,
                        'disponivel' => $disponivel,
                        'observacao' => $observacao,
                    ]

                );
            } else {
                Mencoes::create(
                    [
                        'pessoa_id' => $aluno_id,
                        'modelo_id' => $modelo,
                        'certificable_type' => Mencoes::class,
                        'certificable_id' => null,
                        'assinado_por' => $assinado_por,
                        'atualizado_por' => self::actorId(),
                        'nome' => $nome,
                        'disponivel' => $disponivel,
                        'observacao' => $observacao,
                    ]

                );
            }


            DB::commit();

            Notification::make()
                ->title('Comendas geradas corretamente!')
                ->success()
                ->send();
        } catch (Exception $e) {
            Log::error("Erro ao gerar comenda: {$e->getMessage()}");
            Notification::make()
                ->title('Erro ao gerar comenda')
                ->body("Ocorreu um erro ao tentar gerar a comenda: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }


    public static function gerarCertificadoIndividual($data)
    {

        try {
            DB::beginTransaction();

            $curso_id = $data['curso_id'];

            $atividades_id = $data['cursoatividade_id'] ?? [];

            $aluno_id = $data['aluno_id'];

            $modelo_id = $data['modelo_id'];

            $carga_horaria = $data['carga_horaria'];

            $assinado_por = $data['assinado_por'];

            $conteudo = $data['conteudo'];

            $observacao = $data['observacao'];

            $disponivel = $data['disponivel'];

            if (count($atividades_id) > 0) {
                $certificaveis = CursoAtividades::whereIn('id', $atividades_id)->get();
            } else {
                $certificaveis = Cursos::where('id', $curso_id)->get();
            }
            $gerados = 0;
            $bloqueados = 0;

            foreach ($aluno_id as $user_id) {

                $aluno = User::find($user_id);

                if (! $aluno || $aluno->id == $assinado_por) {
                    continue;
                }

                foreach ($certificaveis as $certificavel) {

                    if (! self::validarAprovacao((int) $certificavel->id, (int) $aluno->id)) {
                        $bloqueados++;
                        Log::warning('CERTIFICADO SERVICE - gerarCertificadoIndividual(): emissao bloqueada para aluno nao aprovado.', [
                            'curso_id' => $certificavel->id,
                            'user_id' => $aluno->id,
                        ]);

                        continue;
                    }

                    $certificavel->certMorph()->create(
                        // [
                        //     'certificable_id' => $certificavel->id,
                        //     'certificable_type' => $certificavel::class,
                        //     'modelo_id' => $modelo_id,
                        //     'pessoa_id' => $aluno->id,
                        // ],
                        [
                            'curso_id' => $certificavel->id,
                            'certificable_id' => $certificavel->id,
                            'certificable_type' => $certificavel::class,
                            'pessoa_id' => $aluno->id,
                            'modelo_id' => $modelo_id,
                            'assinado_por' => $assinado_por,
                            'carga_horaria' => $carga_horaria ?? $certificavel->carga_horaria,
                            'atualizado_por' => self::actorId(),
                            'nome' => $aluno->name,
                            'disponivel' => $disponivel,
                            'conteudo' => $conteudo,
                            'observacao' => $observacao,
                        ]

                    );
                    $gerados++;
                }
            }

            DB::commit();

            $notificacao = Notification::make()
                ->title("{$gerados} certificado(s) gerado(s).")
                ->success();

            if ($bloqueados > 0) {
                $notificacao
                    ->title("{$gerados} certificado(s) gerado(s); {$bloqueados} emissÃ£o(Ãµes) bloqueada(s).")
                    ->warning();
            }

            $notificacao->send();
        } catch (Exception $e) {
            Log::error("Erro ao gerar certificado individual: {$e->getMessage()}");
            Notification::make()
                ->title('Erro ao gerar certificado')
                ->body("Ocorreu um erro ao tentar gerar o certificado individual: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }

    public static function gerarCertificados($data, $curso)
    {
        try {
            DB::beginTransaction();
            $observacao = $data['observacao'];
            $modelo_id = $data['modelo_id'];
            $assinado_por = $data['assinado_por'];
            $observacao = $data['observacao'];
            $disponivel = $data['disponivel'];
            $totalatvd = $data['totalatvd'];



            if ($totalatvd && $curso->temAtividades()) {

                $usersId = $curso->alunos()
                    ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                    ->get()->pluck('aluno_id');

                $users = User::whereIn('id', $usersId)->with('alunoCurso')->with('certificados')->get();

                self::certificarTotalAtvd($data, $curso, $users);

                DB::commit();
                return;
            } else {

                if ($curso->curso_id) {
                    $pai = $curso->curso;
                } else {
                    $pai = $curso;
                }
                $alunos = [];

                $usersId = $curso->alunos()
                    ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                    ->where('aprovado', true)
                    ->get()->pluck('aluno_id');

                $users = User::whereIn('id', $usersId)->with('certificados')->with('alunoCurso')->get();
            }

            foreach ($users as $user) {

                if ($user->certificados->contains('curso_id', $curso->id)) {
                    continue;
                }
                $curso->certMorph()->updateOrCreate(
                    [
                        'curso_id' => $curso->id,
                        'certificable_id' => $curso->id,
                        'modelo_id' => $modelo_id,
                        'pessoa_id' => $user->id,
                        'certificable_type' => $curso::class
                    ],
                    [
                        'certificable_id' => $curso->id,
                        'certificable_type' => $curso::class,
                        'pessoa_id' => $user->id,
                        'modelo_id' => $modelo_id,
                        'assinado_por' => $assinado_por,
                        'atualizado_por' => self::actorId(),
                        // 'titulo' => $titulo ?? $curso->titulo,
                        'titulo' => $curso->titulo,

                        'nome' => $user->name,
                        // 'aquivo' => null,
                        'disponivel' => $disponivel,
                        'observacao' => $observacao,
                    ]
                );
                $alunos[] = $user->alunoCurso->where('curso_id', $pai->id)->first();
            }



            DB::commit();

            EmailService::emailAprovacao($alunos);

            Notification::make()
                ->title('Certificados gerados corretamente!')
                ->success()
                ->send();
        } catch (Exception $e) {
            DB::rollback();
            // dd($e);
            Notification::make()
                ->title('ERRO!' . $e)
                ->danger()
                ->send();
        }
    }

    public static function certificarTotalAtvd($data, $curso, $users)
    {
        $observacao = $data['observacao'];
        $modelo_id = $data['modelo_id'];
        $assinado_por = $data['assinado_por'];
        $observacao = $data['observacao'];
        $disponivel = $data['disponivel'];

        $atividadesId = $curso->atividades->where('certificavel', true)->pluck('id');
        $users->load(['alunoCurso.atividade']);
        $gerados = 0;
        $alunos = [];
        foreach ($users as $user) {
            if ($user->certificados->contains('curso_id', $curso->id)) {
                continue;
            }
            $ch = $user->alunoCurso->whereIn('curso_id', $atividadesId)
                ->where('aprovado', true)
                ->sum(function ($aluno) {
                    return $aluno->atividade->carga_horaria;
                });
            if ($ch <= 0) {
                continue;
            }
            $curso->certMorph()->updateOrCreate(
                [
                    'curso_id' => $curso->id,
                    'certificable_id' => $curso->id,
                    'modelo_id' => $modelo_id,
                    'pessoa_id' => $user->id,
                    'certificable_type' => $curso::class
                ],
                [
                    'certificable_id' => $curso->id,
                    'certificable_type' => $curso::class,
                    'pessoa_id' => $user->id,
                    'modelo_id' => $modelo_id,
                    'assinado_por' => $assinado_por,
                    'atualizado_por' => self::actorId(),
                    'carga_horaria' => $ch,
                    // 'titulo' => $titulo ?? $curso->titulo,
                    'titulo' => $curso->titulo,
                    'nome' => $user->name,
                    // 'arquivo' => null,
                    'disponivel' => $disponivel,
                    'observacao' => $observacao,
                ]
            );
            $gerados++;
            $aluno = $user->alunoCurso->where('curso_id', $curso->id)->first();
            $alunos[] = $aluno;
        }
        Notification::make()
            ->title("{$gerados} Certificados gerados")
            ->success()
            ->send();
        EmailService::emailAprovacao($alunos);
    }


    public static function liberarCertificados($aluno)
    {
        try {

            $atividades_id = [];
            if ($aluno->curso && $aluno->curso->temAtividades()) {
                $atividades_id = $aluno->curso->atividades->pluck('id');
            }
            $atividades_id[] = $aluno->curso->id;

            // dd($atividades_id);

            $certificados = CursoCertificados::where('pessoa_id', $aluno->aluno_id)->whereIn('curso_id', $atividades_id)->get();

            DB::beginTransaction();
            foreach ($certificados as $certificado) {
                $certificado->update(['disponivel' => true]);
            }

            DB::commit();
            EmailService::certificadoLiberado([$aluno]);
            Notification::make()
                ->title("Certificados do curso {$aluno->curso->titulo} estão disponíveis")
                ->sendToDatabase($aluno->aluno);
        } catch (Exception $e) {
            Log::info("Erro ao liberar certificados: {$e->getMessage()} linha {$e->getLine()} arquivo {$e->getFile()}");
            DB::rollback();
        }
    }
}
