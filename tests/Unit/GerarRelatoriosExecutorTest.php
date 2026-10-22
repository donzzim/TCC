<?php

namespace Tests\Unit;

use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Executor\GerarRelatoriosExecutor;
use Tests\TestCase;

class GerarRelatoriosExecutorTest extends TestCase
{
    public function test_executor_skips_when_report_automation_is_disabled(): void
    {
        $result = app(GerarRelatoriosExecutor::class)->execute(
            new Cursos(),
            [],
            [],
        );

        $this->assertSame(AutoStepStatus::PENDING->value, $result['step']['status']);
        $this->assertFalse($result['step']['completed']);
        $this->assertSame('skipped_generate_reports', $result['meta']['action']);
    }

    public function test_executor_blocks_when_no_report_type_is_configured(): void
    {
        $result = app(GerarRelatoriosExecutor::class)->execute(
            new Cursos(),
            [],
            ['relatorios' => ['gerar_automaticamente' => true]],
        );

        $this->assertSame(AutoStepStatus::AWAITING_ACTION->value, $result['step']['status']);
        $this->assertTrue($result['step']['analysis']['blocked']);
        $this->assertSame('missing_tipos_relatorios', $result['pending_actions'][0]['code']);
    }
}
