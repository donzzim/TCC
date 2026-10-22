<?php

namespace App\Services\Auto;

use App\Enums\Auto\AutoStatus;
use App\Filament\Resources\Escola\CursosResource;
use App\Models\Escola\Cursos;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AutoNotificationService
{
    public function notifyAfterClassification(
        Cursos $curso,
        array $analysis,
        array $autoStatus,
        ?array $previousAutoStatus = null,
    ): void {
        if (! $this->shouldNotifyStatus($curso)) {
            return;
        }

        $recipients = $this->responsibleUsers($curso);

        if ($recipients->isEmpty()) {
            Log::info('Automacao sem responsaveis configurados para notificacao.', [
                'curso_id' => $curso->id,
                'status' => $autoStatus['status'] ?? null,
            ]);

            return;
        }

        if (! $this->shouldDispatchNotification($autoStatus, $previousAutoStatus)) {
            return;
        }

        $notification = $this->makeStatusNotification($curso, $analysis, $autoStatus);

        $recipients->each(
            fn(User $recipient) => $notification->sendToDatabase($recipient)
        );
    }

    public function notifyBreakpointsAfterClassification(
        Cursos $curso,
        array $analysis,
        array $autoStatus,
        ?array $previousAutoStatus = null,
    ): void {
        $breakpointActions = $this->notifiableBreakpointActions(
            $autoStatus['pending_actions'] ?? $analysis['pending_actions'] ?? []
        );

        if ($breakpointActions->isEmpty()) {
            return;
        }

        $previousBreakpointCodes = $this->notifiableBreakpointActions(
            $previousAutoStatus['pending_actions'] ?? []
        )
            ->pluck('code')
            ->filter()
            ->unique()
            ->values();

        $newBreakpointActions = $breakpointActions
            ->reject(fn(array $action): bool => $previousBreakpointCodes->contains($action['code'] ?? null))
            ->values();

        if ($newBreakpointActions->isEmpty()) {
            return;
        }

        $recipients = $this->responsibleUsers($curso);

        if ($recipients->isEmpty()) {
            Log::info('Breakpoint de automacao sem responsaveis configurados para notificacao.', [
                'curso_id' => $curso->id,
                'breakpoints' => $newBreakpointActions->pluck('code')->all(),
            ]);

            return;
        }

        $newBreakpointActions->each(function (array $action) use ($curso, $recipients): void {
            $notification = $this->makeBreakpointNotification($curso, $action);

            $recipients->each(
                fn(User $recipient) => $notification->sendToDatabase($recipient)
            );
        });
    }

    public static function notifyStep(Cursos $curso, array $analysis = [], array $autoStatus = []): void
    {
        app(self::class)->notifyAfterClassification($curso, $analysis, $autoStatus);
    }

    public static function notifyBreakPoint(Cursos $curso, array $analysis = [], array $autoStatus = []): void
    {
        app(self::class)->notifyBreakpointsAfterClassification($curso, $analysis, $autoStatus);
    }

    public static function notifyAwaitingAction(Cursos $curso, array $analysis = [], array $autoStatus = []): void
    {
        app(self::class)->notifyAfterClassification($curso, $analysis, $autoStatus);
    }

    public static function notifyPendingFields(Cursos $curso, array $fields): void
    {
        $pendingActions = collect($fields)
            ->map(fn(mixed $field): array => [
                'code' => is_string($field) ? $field : 'pending_field',
                'message' => is_string($field) ? $field : 'Campo pendente na automacao.',
                'blocking' => true,
                'severity' => 'warning',
            ])
            ->values()
            ->all();

        app(self::class)->notifyAfterClassification(
            curso: $curso,
            analysis: ['pending_actions' => $pendingActions],
            autoStatus: [
                'status' => AutoStatus::AGUARDANDO_ACAO->value,
                'reason' => 'Ha campos pendentes na configuracao da automacao.',
                'pending_actions' => $pendingActions,
                'last_analyzed_at' => now(),
            ],
        );
    }

    /**
     * Notifica os destinatarios dos relatorios com um link para o lote gerado.
     *
     * O link e protegido pela rota de relatorios, que valida novamente os
     * destinatarios registrados no lote antes de liberar qualquer arquivo.
     *
     * @param  array<int, int|string>  $recipientIds
     * @param  array<int, string>  $reportLabels
     */
    public function notifyReportsGenerated(
        Cursos $curso,
        array $recipientIds,
        string $reportsUrl,
        int $reportsCount,
        array $reportLabels = [],
    ): void {
        $recipients = User::query()
            ->whereIn('id', collect($recipientIds)->map(fn(mixed $id): int => (int) $id)->unique()->all())
            ->get();

        if ($recipients->isEmpty()) {
            Log::info('Relatorios automaticos sem destinatarios para notificacao Filament.', [
                'curso_id' => $curso->id,
                'recipient_ids' => $recipientIds,
            ]);

            return;
        }

        $label = $reportsCount === 1 ? 'relatorio foi gerado' : 'relatorios foram gerados';
        $notification = Notification::make()
            ->title("Relatorios do curso: {$curso->titulo}")
            ->body("{$reportsCount} {$label} e estao disponiveis para consulta.")
            ->success()
            ->actions([
                Action::make('abrir_relatorios')
                    ->label('Abrir relatorios')
                    ->icon('heroicon-o-document-chart-bar')
                    ->url($reportsUrl)
                    ->openUrlInNewTab(),
            ]);

        $recipients->each(
            fn(User $recipient) => $notification->sendToDatabase($recipient)
        );

        Log::info('Notificacao Filament de relatorios automaticos enviada.', [
            'curso_id' => $curso->id,
            'recipient_ids' => $recipients->pluck('id')->all(),
            'reports_count' => $reportsCount,
            'report_labels' => $reportLabels,
        ]);
    }

    private function shouldNotifyStatus(Cursos $curso): bool
    {
        return (bool) data_get($curso->auto_config, 'notificacao.notificar_status', false);
    }

    private function responsibleUsers(Cursos $curso): Collection
    {
        $responsibleIds = collect(data_get($curso->auto_config, 'notificacao.responsaveis.list', []))
            ->filter(fn(mixed $id): bool => filled($id))
            ->unique()
            ->values();

        if ($responsibleIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $responsibleIds->all())
            ->get();
    }

    private function shouldDispatchNotification(array $autoStatus, ?array $previousAutoStatus): bool
    {
        if (! $previousAutoStatus) {
            return true;
        }

        if (($previousAutoStatus['status'] ?? null) !== ($autoStatus['status'] ?? null)) {
            return true;
        }

        $previousBlockingCodes = $this->blockingPendingCodes($previousAutoStatus['pending_actions'] ?? []);
        $currentBlockingCodes = $this->blockingPendingCodes($autoStatus['pending_actions'] ?? []);

        return $currentBlockingCodes->diff($previousBlockingCodes)->isNotEmpty();
    }

    private function blockingPendingCodes(array $pendingActions): Collection
    {
        return collect($pendingActions)
            ->filter(fn(mixed $action): bool => is_array($action) && ($action['blocking'] ?? false))
            ->map(fn(array $action): string => (string) ($action['code'] ?? $action['message'] ?? 'pending_action'))
            ->filter()
            ->unique()
            ->values();
    }

    private function notifiableBreakpointActions(array $pendingActions): Collection
    {
        return collect($pendingActions)
            ->filter(function (mixed $action): bool {
                if (! is_array($action)) {
                    return false;
                }

                $isBreakpoint = ($action['type'] ?? null) === 'breakpoint'
                    || str_starts_with((string) ($action['code'] ?? ''), 'manual_breakpoint_');

                return $isBreakpoint && (bool) ($action['notify'] ?? false);
            })
            ->values();
    }

    private function makeStatusNotification(Cursos $curso, array $analysis, array $autoStatus): Notification
    {
        $status = (string) ($autoStatus['status'] ?? AutoStatus::ATIVO->value);
        $reason = (string) ($autoStatus['reason'] ?? 'Status da automacao atualizado.');
        $pendingCount = count($autoStatus['pending_actions'] ?? []);
        $dispatchableCount = count($autoStatus['confirmed_dispatchable_steps'] ?? []);

        $bodyParts = [$reason];

        if ($pendingCount > 0) {
            $bodyParts[] = "{$pendingCount} pendencia(s) registrada(s).";
        }

        if ($dispatchableCount > 0) {
            $bodyParts[] = "{$dispatchableCount} etapa(s) pronta(s) para execucao.";
        }

        $notification = Notification::make()
            ->title("Automacao do curso: {$curso->titulo}")
            ->body(implode(' ', $bodyParts));

        return match ($status) {
            AutoStatus::ERRO->value => $notification->danger(),
            AutoStatus::AGUARDANDO_ACAO->value, AutoStatus::PAUSADO->value => $notification->warning(),
            AutoStatus::CONCLUIDO->value => $notification->success(),
            default => $notification->info(),
        };
    }

    private function makeBreakpointNotification(Cursos $curso, array $action): Notification
    {
        $breakpoint = is_array($action['breakpoint'] ?? null) ? $action['breakpoint'] : [];
        $label = (string) ($breakpoint['label'] ?? $action['message'] ?? 'Breakpoint de automacao');
        $step = (string) ($breakpoint['step'] ?? $action['step'] ?? 'etapa');
        $position = (string) ($breakpoint['position'] ?? 'before');
        $description = $breakpoint['description'] ?? null;
        $blocking = (bool) ($breakpoint['blocking'] ?? $action['blocking'] ?? false);
        $severity = (string) ($breakpoint['severity'] ?? $action['severity'] ?? 'warning');

        $positionLabel = $position === 'after' ? 'depois da etapa' : 'antes da etapa';
        $bodyParts = [
            "O fluxo chegou ao breakpoint {$positionLabel} {$step}.",
        ];

        if (filled($description)) {
            $bodyParts[] = (string) $description;
        }

        $bodyParts[] = $blocking
            ? 'A execucao automatica foi pausada para revisao da equipe.'
            : 'A execucao automatica nao foi pausada por este breakpoint.';

        $notification = Notification::make()
            ->title("Breakpoint: {$label}")
            ->body(implode(' ', $bodyParts))
            ->actions([
                Action::make('abrir_fluxo')
                    ->label('Abrir fluxo')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(CursosResource::getUrl('index', [
                        'tableAction' => 'autoConfig',
                        'tableActionRecord' => $curso->getKey(),
                    ])),
            ]);

        return match ($severity) {
            'error' => $notification->danger(),
            'warning' => $notification->warning(),
            default => $notification->info(),
        };
    }
}
