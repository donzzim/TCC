<?php

namespace Tests\Unit;

use App\Services\Auto\Support\StepExecutionFactory;
use App\Services\Auto\Support\StepStrategyFactory;
use InvalidArgumentException;
use Tests\TestCase;

class StepFactoriesTest extends TestCase
{
    public function test_execution_factory_discovers_all_registered_executors(): void
    {
        $factory = app(StepExecutionFactory::class);

        $this->assertSame([
            'abrir_inscricao',
            'confirmar_inscricao',
            'encerrar_inscricao',
            'gerar_certificados',
            'gerar_certificados_instrutores',
            'gerar_relatorios',
            'processar_aprovacoes',
        ], collect($factory->keys())->sort()->values()->all());

        $this->assertTrue($factory->has('abrir_inscricao'));
        $this->assertSame('abrir_inscricao', $factory->make('abrir_inscricao')::stepKey());
    }

    public function test_execution_factory_rejects_an_unknown_step(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(StepExecutionFactory::class)->make('step_inexistente');
    }

    public function test_strategy_factory_discovers_all_registered_strategies(): void
    {
        $factory = app(StepStrategyFactory::class);

        $this->assertSame([
            'abrir_inscricao',
            'confirmar_inscricao',
            'encerrar_inscricao',
            'gerar_certificados',
            'gerar_certificados_instrutores',
            'gerar_relatorios',
            'processar_aprovacoes',
        ], collect($factory->all())->keys()->sort()->values()->all());

        $this->assertSame('processar_aprovacoes', $factory->make('processar_aprovacoes')::$step_key);
    }

    public function test_strategy_factory_rejects_an_unknown_step(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(StepStrategyFactory::class)->make('step_inexistente');
    }
}
