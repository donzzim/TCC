<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRequestTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLog($request)) {
            return $next($request);
        }

        if ($request->attributes->get('emes.request_timing_active') === true) {
            return $next($request);
        }

        $request->attributes->set('emes.request_timing_active', true);

        $middlewareStart = microtime(true);
        $phpStart = defined('LARAVEL_START') ? (float) LARAVEL_START : $middlewareStart;
        $autoloadMs = defined('EMES_AUTOLOAD_END')
            ? round(max(0, ((float) constant('EMES_AUTOLOAD_END') - $phpStart) * 1000), 2)
            : null;
        $appBootstrapMs = defined('EMES_APP_BOOTSTRAP_END') && defined('EMES_AUTOLOAD_END')
            ? round(max(0, ((float) constant('EMES_APP_BOOTSTRAP_END') - (float) constant('EMES_AUTOLOAD_END')) * 1000), 2)
            : null;
        $requestId = (string) ($request->headers->get('X-Request-Id') ?: Str::uuid());
        $livewireComponents = $this->livewireComponents($request);
        $sqlTotal = 0;
        $sqlCount = 0;
        $queries = [];
        $activeQuery = null;
        $lastCompletedQuery = null;
        $logged = false;

        foreach (array_unique(array_filter([config('database.default'), 'emes'])) as $connectionName) {
            DB::connection($connectionName)->beforeExecuting(function (string $sql, array $bindings, Connection $connection) use (&$activeQuery): void {
                $activeQuery = $this->querySnapshot($sql, $bindings, $connection->getName());
            });
        }

        DB::listen(function ($query) use (&$sqlTotal, &$sqlCount, &$queries, &$activeQuery, &$lastCompletedQuery) {
            $sqlTotal += $query->time;
            $sqlCount++;

            $sql = $this->normalizeSql($query->sql);
            $connectionName = $query->connectionName ?: $query->connection->getName();
            $queryKey = "{$connectionName}:{$sql}";

            $lastCompletedQuery = [
                ...$this->querySnapshot($query->sql, $query->bindings, $connectionName),
                'time_ms' => round($query->time, 2),
            ];

            $activeQuery = null;

            if (! isset($queries[$queryKey])) {
                $queries[$queryKey] = [
                    'count' => 0,
                    'time_ms' => 0,
                    'connection' => $connectionName,
                    'SQL' => $sql,
                    // 'sample_bindings' => $this->sampleBindings($query->bindings),
                ];
            }

            $queries[$queryKey]['count']++;
            $queries[$queryKey]['time_ms'] += $query->time;
        });

        $writeLog = function (string $status, ?array $error = null, ?Response $response = null) use (
            &$logged,
            &$queries,
            &$sqlTotal,
            &$sqlCount,
            &$activeQuery,
            &$lastCompletedQuery,
            $request,
            $middlewareStart,
            $phpStart,
            $autoloadMs,
            $appBootstrapMs,
            $requestId,
            $livewireComponents
        ): void {
            if ($logged) {
                return;
            }

            $logged = true;
            $now = microtime(true);
            $durationMs = round(($now - $phpStart) * 1000, 2);
            $middlewareDurationMs = round(($now - $middlewareStart) * 1000, 2);
            $bootstrapDurationMs = round(max(0, $durationMs - $middlewareDurationMs), 2);
            $unattributedPreMiddlewareMs = $bootstrapDurationMs;

            if ($autoloadMs !== null) {
                $unattributedPreMiddlewareMs -= $autoloadMs;
            }

            if ($appBootstrapMs !== null) {
                $unattributedPreMiddlewareMs -= $appBootstrapMs;
            }

            $unattributedPreMiddlewareMs = round(max(0, $unattributedPreMiddlewareMs), 2);
            $phpNonSqlMs = round(max(0, $durationMs - $sqlTotal), 2);
            $route = $request->route();
            $responseStatus = $response?->getStatusCode();

            if ($response) {
                $this->addTimingHeaders(
                    $response,
                    $requestId,
                    $durationMs,
                    $sqlTotal,
                    $sqlCount,
                    $bootstrapDurationMs,
                    $middlewareDurationMs,
                    $autoloadMs,
                    $appBootstrapMs,
                    $unattributedPreMiddlewareMs,
                );
            }

            logger()->info('REQ_TIMING', [
                'request_id' => $requestId,
                'status' => $status,
                'path' => $request->path(),
                'method' => $request->method(),
                'route_name' => $route?->getName(),
                'route_uri' => $route?->uri(),
                'route_action' => $route?->getActionName(),
                'is_livewire' => $this->isLivewireRequest($request),
                'livewire_components' => $livewireComponents,
                'status_code' => $responseStatus,
                'duration_ms' => $durationMs,
                'bootstrap_ms' => $bootstrapDurationMs,
                'middleware_ms' => $middlewareDurationMs,
                'autoload_ms' => $autoloadMs,
                'app_bootstrap_ms' => $appBootstrapMs,
                'unattributed_pre_middleware_ms' => $unattributedPreMiddlewareMs,
                'sql_total_ms' => round($sqlTotal, 2),
                'sql_count' => $sqlCount,
                'php_non_sql_ms' => $phpNonSqlMs,
                'sql_share_percent' => $durationMs > 0 ? round(($sqlTotal / $durationMs) * 100, 2) : 0,
                'slow_class' => $this->slowClass($durationMs),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'active_query' => $activeQuery,
                'last_completed_query' => $lastCompletedQuery,
                'top_queries_by_time' => $this->topQueries($queries, 'time_ms'),
                'top_queries_by_count' => $this->topQueries($queries, 'count'),
                'error' => $error,
            ]);
        };

        register_shutdown_function(function () use ($writeLog): void {
            $error = error_get_last();

            if (! $error || ! in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
                return;
            }

            $writeLog('fatal', [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        });

        try {
            $response = $next($request);
            $writeLog('completed', response: $response);

            return $response;
        } catch (Throwable $exception) {
            $writeLog('exception', [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }

    private function shouldLog(Request $request): bool
    {
        if (! config('app.debug') && ! app()->environment(['local', 'dev', 'development', 'testing'])) {
            return false;
        }

        return $request->is(
            'admin*',
            'emes/admin*',
            'app*',
            'emes/app*',
            'livewire*',
            '*/livewire*',
            'egap*',
            'emes/egap*',
        );
    }

    private function isLivewireRequest(Request $request): bool
    {
        return $request->is('livewire*')
            || $request->is('*/livewire*')
            || $request->headers->has('X-Livewire')
            || str_ends_with('/' . trim($request->path(), '/'), '/livewire/update');
    }

    /**
     * @return list<string>
     */
    private function livewireComponents(Request $request): array
    {
        if (! $this->isLivewireRequest($request)) {
            return [];
        }

        $components = $request->input('components', []);

        if (! is_array($components)) {
            return [];
        }

        $names = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $memo = $component['memo'] ?? [];
            $snapshot = $component['snapshot'] ?? null;

            if (is_string($snapshot)) {
                $snapshot = json_decode($snapshot, true);
                $memo = is_array($snapshot) ? ($snapshot['memo'] ?? []) : $memo;
            }

            $name = is_array($memo) ? ($memo['name'] ?? null) : null;

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    private function slowClass(float $durationMs): string
    {
        return match (true) {
            $durationMs >= 1000 => 'critical',
            $durationMs >= 300 => 'slow',
            $durationMs >= 100 => 'attention',
            default => 'ok',
        };
    }

    private function addTimingHeaders(
        Response $response,
        string $requestId,
        float $durationMs,
        float $sqlTotal,
        int $sqlCount,
        float $bootstrapDurationMs,
        float $middlewareDurationMs,
        ?float $autoloadMs,
        ?float $appBootstrapMs,
        float $unattributedPreMiddlewareMs,
    ): void
    {
        $phpNonSqlMs = max(0, $durationMs - $sqlTotal);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-App-Duration-Ms', (string) $durationMs);
        $response->headers->set('X-Db-Duration-Ms', (string) round($sqlTotal, 2));
        $serverTiming = sprintf(
            'app;dur=%.2f, db;dur=%.2f, php;dur=%.2f, bootstrap;dur=%.2f, middleware;dur=%.2f',
            $durationMs,
            $sqlTotal,
            $phpNonSqlMs,
            $bootstrapDurationMs,
            $middlewareDurationMs,
        );

        if ($autoloadMs !== null) {
            $serverTiming .= sprintf(', autoload;dur=%.2f', $autoloadMs);
        }

        if ($appBootstrapMs !== null) {
            $serverTiming .= sprintf(', appboot;dur=%.2f', $appBootstrapMs);
        }

        $serverTiming .= sprintf(', preother;dur=%.2f, dbq;desc="%d queries"',
            $unattributedPreMiddlewareMs,
            $sqlCount,
        );

        $response->headers->set('Server-Timing', $serverTiming);
    }

    private function querySnapshot(string $sql, array $bindings, string $connectionName): array
    {
        return [
            'connection' => $connectionName,
            'SQL' => $this->normalizeSql($sql),
            // 'sample_bindings' => $this->sampleBindings($bindings),
        ];
    }

    private function normalizeSql(string $sql): string
    {
        return mb_strimwidth(preg_replace('/\s+/', ' ', trim($sql)), 0, 1200, '...');
    }

    private function topQueries(array $queries, string $sortBy): array
    {
        uasort($queries, fn(array $a, array $b): int => ($b[$sortBy] ?? 0) <=> ($a[$sortBy] ?? 0));

        return array_values(array_slice($queries, 0, 10));
    }
}
