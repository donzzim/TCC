<?php

namespace App\Services\Auto\Strategy;

use App\Models\Escola\Cursos;

interface StepStrategyInterface
{
    public function reconcile(Cursos $curso, array $step, array $auto_config, array &$pending_actions): array;

    public function resetAnalysis(array &$step): void;

    public function resolveEnabled(array &$step, array $auto_config): void;

    public function analyzeConditions(Cursos $curso, array &$step, array $auto_config, array &$pending_actions): void;

    public function resolveStatus(Cursos $curso, array &$step, array $auto_config, array &$pending_actions): void;
}
