<?php

namespace Tests\Unit;

use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Executor\AbrirInscricaoExecutor;
use App\Services\Auto\Executor\EncerrarInscricaoExecutor;
use App\Services\Auto\Strategy\AbrirInscricaoStepStrategy;
use App\Services\Auto\Strategy\EncerrarInscricaoStepStrategy;
use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;
use Tests\TestCase;

class AutoRegistrationStateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_opening_strategy_is_pending_when_disabled(): void
    {
        $step = $this->reconcile(
            new AbrirInscricaoStepStrategy(),
            new Cursos(),
            [],
        );

        $this->assertSame(AutoStepStatus::PENDING->value, $step['status']);
        $this->assertFalse($step['enabled']);
        $this->assertFalse($step['analysis']['blocked']);
    }

    public function test_opening_strategy_blocks_a_course_without_registration_start(): void
    {
        $pendingActions = [];
        $step = AutoStructureBuilder::generateDefaultStep();
        $step = (new AbrirInscricaoStepStrategy())->reconcile(
            new Cursos(),
            $step,
            ['inscricao' => ['abertura_automatica' => true]],
            $pendingActions,
        );

        $this->assertSame(AutoStepStatus::AWAITING_ACTION->value, $step['status']);
        $this->assertTrue($step['analysis']['blocked']);
        $this->assertSame('missing_inicio_inscricao', $pendingActions[0]['code']);
    }

    public function test_opening_strategy_marks_the_step_ready_after_registration_start(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 10:00:00'));
        $curso = new Cursos(['inicio_inscricao' => Carbon::parse('2026-10-02 09:00:00')]);
        $pendingActions = [];

        $step = (new AbrirInscricaoStepStrategy())->reconcile(
            $curso,
            AutoStructureBuilder::generateDefaultStep(),
            ['inscricao' => ['abertura_automatica' => true]],
            $pendingActions,
        );

        $this->assertSame(AutoStepStatus::READY->value, $step['status']);
        $this->assertTrue($step['analysis']['eligible']);
        $this->assertSame([], $pendingActions);
    }

    public function test_closing_strategy_blocks_a_course_without_registration_end(): void
    {
        $pendingActions = [];

        $step = (new EncerrarInscricaoStepStrategy())->reconcile(
            new Cursos(),
            AutoStructureBuilder::generateDefaultStep(),
            ['inscricao' => ['encerramento_automatico' => true]],
            $pendingActions,
        );

        $this->assertSame(AutoStepStatus::AWAITING_ACTION->value, $step['status']);
        $this->assertTrue($step['analysis']['blocked']);
        $this->assertSame('missing_termino_inscricao', $pendingActions[0]['code']);
    }

    public function test_executors_skip_disabled_registration_automation_without_persisting(): void
    {
        $curso = new Cursos();

        $opening = app(AbrirInscricaoExecutor::class)->execute($curso, [], []);
        $closing = app(EncerrarInscricaoExecutor::class)->execute($curso, [], []);

        $this->assertFalse($opening['step']['completed']);
        $this->assertSame(AutoStepStatus::SKIPPED->value, $opening['step']['status']);
        $this->assertSame('skipped_open_registration', $opening['meta']['action']);
        $this->assertSame(AutoStepStatus::SKIPPED->value, $closing['step']['status']);
        $this->assertSame('skipped_close_registration', $closing['meta']['action']);
    }

    private function reconcile(object $strategy, Cursos $curso, array $config): array
    {
        $pendingActions = [];

        return $strategy->reconcile(
            $curso,
            AutoStructureBuilder::generateDefaultStep(),
            $config,
            $pendingActions,
        );
    }
}
