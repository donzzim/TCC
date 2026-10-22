<?php

namespace App\Services\Auto\Support;

use App\Services\Auto\Strategy\AbrirInscricaoStepStrategy;
use App\Services\Auto\Strategy\StepStrategyInterface;
use Symfony\Component\HttpKernel\Attribute\Cache;

class StepStrategyFactory
{
    protected array $strategies = [];

    public function __construct()
    {
        $this->registerStrategies();
    }

    protected function registerStrategies(): void
    {
        foreach ($this->discoverStrategies() as $class) {
            $instance = app($class);

            $key = $class::$step_key ?? null;

            if (!$key) {
                throw new \Exception("Strategy {$class} não possui stepKey.");
            }

            $this->strategies[$key] = $instance;
        }
    }

    protected function discoverStrategies(): array
    {
        return collect(glob(app_path('Services/Auto/Strategy/*.php')))
            ->map(function ($file) {
                return 'App\\Services\\Auto\\Strategy\\' . basename($file, '.php');
            })
            ->filter(function ($class) {
                return class_exists($class)
                    && in_array(StepStrategyInterface::class, class_implements($class));
            })
            ->values()
            ->all();
    }

    public function make(string $stepKey): StepStrategyInterface
    {
        if (!isset($this->strategies[$stepKey])) {
            throw new \InvalidArgumentException("Strategy não encontrada para step: {$stepKey}");
        }

        return $this->strategies[$stepKey];
    }

    public function all(): array
    {
        return $this->strategies;
    }
}
