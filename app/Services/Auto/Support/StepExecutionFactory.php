<?php

namespace App\Services\Auto\Support;

use App\Services\Auto\Executor\AutoStepExecutorInterface;
use InvalidArgumentException;
use RuntimeException;

class StepExecutionFactory
{
    /**
     * @var array<string, AutoStepExecutorInterface>
     */
    protected array $executors = [];

    public function __construct()
    {
        $this->registerExecutors();
    }

    protected function registerExecutors(): void
    {
        foreach ($this->discoverExecutors() as $class) {
            /** @var class-string<AutoStepExecutorInterface> $class */
            $key = $class::stepKey();

            if (! $key) {
                throw new RuntimeException("Executor {$class} nao declarou stepKey.");
            }

            $this->executors[$key] = app($class);
        }
    }

    protected function discoverExecutors(): array
    {
        $files = glob(app_path('Services/Auto/Executor/*.php')) ?: [];

        return collect($files)
            ->map(fn(string $file): string => 'App\\Services\\Auto\\Executor\\' . basename($file, '.php'))
            ->filter(fn(string $class): bool => class_exists($class)
                && is_subclass_of($class, AutoStepExecutorInterface::class))
            ->values()
            ->all();
    }

    public function make(string $stepKey): AutoStepExecutorInterface
    {
        if (! isset($this->executors[$stepKey])) {
            throw new InvalidArgumentException("Executor nao encontrado para step: {$stepKey}");
        }

        return $this->executors[$stepKey];
    }

    public function has(string $stepKey): bool
    {
        return isset($this->executors[$stepKey]);
    }

    public function all(): array
    {
        return $this->executors;
    }

    public function keys(): array
    {
        return array_keys($this->executors);
    }
}
