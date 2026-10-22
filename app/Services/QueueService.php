<?php

namespace App\Services;

use App\Models\Settings;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as FacadesNotification;

class QueueService
{
    protected string $settingsKey = 'queue_worker_control';

    public function ensureRunning(): array
    {
        try {
            $status = $this->status();

            if ($status['running']) {
                return [
                    'success' => true,
                    'message' => 'Worker já está rodando.',
                    'status' => $status,
                ];
            }

            if (! $status['enabled']) {
                $admin = User::find(1);
                if ($admin) {
                    FacadesNotification::sendNow(
                        $admin,
                        Notification::make()
                            ->title('Worker da fila caiu')
                            ->body('O worker da fila caiu, mas não será reiniciado porque foi desabilitado manualmente. Verifique o status do worker.')
                            ->warning()
                            ->toDatabase()
                    );
                }
                return [
                    'success' => true,
                    'message' => 'Worker parado manualmente. Não será reiniciado.',
                    'status' => $status,
                ];
            }

            $result = $this->start();

            $admin = User::find(1);

            if ($result['success']) {
                if ($admin) {
                    FacadesNotification::sendNow(
                        $admin,
                        Notification::make()
                            ->title('Worker da fila reiniciado')
                            ->body('O worker da fila caiu, porém foi reiniciado automaticamente com sucesso às ' . now()->toDateTimeString() . '.')
                            ->success()
                            ->toDatabase()
                    );
                }

                return $result;
            }

            if ($admin) {
                FacadesNotification::sendNow(
                    $admin,
                    Notification::make()
                        ->title('Worker da fila caiu')
                        ->body('O sistema tentou reiniciar o worker, mas não conseguiu.')
                        ->danger()
                        ->toDatabase()
                );
            }

            Log::info('Worker da fila caiu e falhou ao reiniciar automaticamente.');

            return $result;
        } catch (\Throwable $e) {
            Log::error('Erro ao verificar ou reiniciar o worker da fila.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao verificar ou reiniciar o worker.',
                'status' => null,
            ];
        }
    }

    public function status(): array
    {
        $value = $this->getValue();

        $pid = isset($value['pid']) ? (int) $value['pid'] : null;
        $running = $pid ? $this->pidExists($pid) : false;

        /**
         * Caso o PID salvo não exista mais, limpamos.
         * Mas NÃO mexemos no enabled, porque enabled é intenção,
         * não estado real do processo.
         */
        if (! $running && $pid) {
            $value['pid'] = null;
            $value['last_error'] = 'Worker estava registrado, mas o processo não está mais ativo.';
            $value['last_check_at'] = now()->toDateTimeString();

            $this->saveValue($value);

            $pid = null;
        }

        /**
         * Se não existe PID salvo, ou o PID salvo morreu,
         * procura um processo queue:work real no sistema.
         */
        if (! $running) {
            $pids = $this->getQueueWorkerPids();

            if (! empty($pids)) {
                $pid = $pids[0];
                $running = true;

                $value['pid'] = $pid;
                $value['last_error'] = null;
                $value['last_check_at'] = now()->toDateTimeString();

                $this->saveValue($value);
            }
        }

        return [
            'enabled' => (bool) ($value['enabled'] ?? false),
            'running' => $running,
            'pid' => $running ? $pid : null,
            'last_error' => $value['last_error'] ?? null,
            'started_at' => $value['started_at'] ?? null,
            'last_check_at' => $value['last_check_at'] ?? null,
        ];
    }

    public function start(): array
    {
        $lock = Cache::lock('queue-worker-control', 10);

        if (! $lock->get()) {
            return [
                'success' => false,
                'message' => 'Operação já em andamento.',
            ];
        }

        try {
            $status = $this->status();

            if ($status['running']) {
                $value = $this->getValue();

                $value['enabled'] = true;
                $value['pid'] = $status['pid'];
                $value['last_error'] = null;
                $value['last_check_at'] = now()->toDateTimeString();

                $this->saveValue($value);

                return [
                    'success' => true,
                    'message' => 'Worker já está em execução.',
                    'status' => $this->status(),
                ];
            }

            $command = 'cd /var/www/html/emes && nohup php artisan queue:work -v --tries=3 --timeout=120 --sleep=1 --memory=512 >> storage/logs/queue-worker.log 2>&1 & echo $!';

            exec($command, $output, $resultCode);

            $pid = isset($output[0]) ? (int) trim($output[0]) : null;

            sleep(1);

            $running = $pid ? $this->pidExists($pid) : false;

            /**
             * Fallback: às vezes o echo $! pode não bater certinho.
             * Então buscamos no SO também.
             */
            if (! $running) {
                $pids = $this->getQueueWorkerPids();

                if (! empty($pids)) {
                    $pid = $pids[0];
                    $running = true;
                }
            }

            $value = $this->getValue();

            $value['enabled'] = true;
            $value['pid'] = $running ? $pid : null;
            $value['started_at'] = $running ? now()->toDateTimeString() : ($value['started_at'] ?? null);
            $value['last_check_at'] = now()->toDateTimeString();
            $value['last_error'] = $running ? null : 'Falha ao iniciar o worker.';

            $this->saveValue($value);

            return [
                'success' => $running,
                'message' => $running ? 'Worker iniciado com sucesso.' : 'Falha ao iniciar o worker.',
                'status' => $this->status(),
            ];
        } finally {
            optional($lock)->release();
        }
    }

    public function stop(): array
    {
        $lock = Cache::lock('queue-worker-control', 10);

        if (! $lock->get()) {
            return [
                'success' => false,
                'message' => 'Operação já em andamento.',
            ];
        }

        try {
            $value = $this->getValue();

            $pid = isset($value['pid']) ? (int) $value['pid'] : null;

            /**
             * Mesmo sem PID salvo, tenta achar worker real.
             */
            if (! $pid) {
                $pids = $this->getQueueWorkerPids();
                $pid = $pids[0] ?? null;
            }

            if (! $pid || ! $this->pidExists($pid)) {
                $value['enabled'] = false;
                $value['pid'] = null;
                $value['last_check_at'] = now()->toDateTimeString();

                $this->saveValue($value);

                return [
                    'success' => true,
                    'message' => 'O worker já estava parado.',
                    'status' => $this->status(),
                ];
            }

            exec('kill ' . (int) $pid);

            sleep(1);

            $running = $this->pidExists($pid);

            $value['enabled'] = false;
            $value['pid'] = $running ? $pid : null;
            $value['last_check_at'] = now()->toDateTimeString();
            $value['last_error'] = $running ? 'Não foi possível encerrar o worker.' : null;

            $this->saveValue($value);

            return [
                'success' => ! $running,
                'message' => ! $running ? 'Worker encerrado com sucesso.' : 'Falha ao encerrar o worker.',
                'status' => $this->status(),
            ];
        } finally {
            optional($lock)->release();
        }
    }

    protected function getValue(): array
    {
        return Settings::firstOrCreate(
            ['key' => $this->settingsKey],
            [
                'description' => 'Controle do worker da fila',
                'active' => true,
                'value' => [],
            ]
        )->value ?? [];
    }

    protected function saveValue(array $value): void
    {
        Settings::updateOrCreate(
            ['key' => $this->settingsKey],
            [
                'description' => 'Controle do worker da fila',
                'active' => true,
                'value' => $value,
            ]
        );
    }

    protected function pidExists(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        exec('ps -p ' . (int) $pid . ' -o pid=', $output, $code);

        return ! empty($output);
    }

    protected function getQueueWorkerPids(): array
    {
        exec("pgrep -f '[p]hp artisan queue:work'", $output);

        return array_values(
            array_filter(
                array_map('intval', $output)
            )
        );
    }
}
