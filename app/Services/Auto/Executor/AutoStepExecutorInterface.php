<?php

namespace App\Services\Auto\Executor;

use App\Models\Escola\Cursos;

interface AutoStepExecutorInterface
{
    /**
     * Chave do step que este executor atende.
     *
     * Deve corresponder as chaves de auto_steps usadas pelo analyzer e pelo formulario,
     * como abrir_inscricao, processar_aprovacoes ou gerar_certificados.
     */
    public static function stepKey(): string;

    /**
     * Executa o efeito de dominio de uma etapa ja confirmada como despachavel.
     *
     * O executor deve se limitar a acao da etapa e devolver as alteracoes esperadas
     * para o step. Persistencia, notificacoes gerais, breakpoints e nova analise ficam
     * sob responsabilidade do servico/job que orquestra a execucao.
     *
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $autoConfig
     * @param  array{
     *     analysis?: array<string, mixed>,
     *     auto_status?: array<string, mixed>,
     *     triggered_by?: string|null
     * }  $context
     * @return array{
     *     step?: array<string, mixed>,
     *     pending_actions?: array<int, array<string, mixed>>,
     *     meta?: array<string, mixed>,
     *     should_reanalyze?: bool
     * }
     */
    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array;
}
