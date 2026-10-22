<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Filament\Pages\Escola\RelatoriosEscola;
use App\Http\Controllers\ReportProjetoPedagogicoController;
use App\Mail\RelatoriosAutomaticosMail;
use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\Auto\AutoNotificationService;
use App\Services\Auto\Support\AutoStructureBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class GerarRelatoriosExecutor implements AutoStepExecutorInterface
{
    public function __construct(
        private AutoNotificationService $notificationService,
    ) {}

    public static function stepKey(): string
    {
        return 'gerar_relatorios';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'relatorios.gerar_automaticamente'))) {
            return $this->skip('Geracao automatica de relatorios desativada na configuracao.');
        }

        $reportTypes = $this->normalizeIds(data_get($autoConfig, 'relatorios.tipos', []));
        $availableTypes = RelatoriosEscola::getReportTypeOptions();
        $invalidTypes = array_values(array_diff($reportTypes, array_keys($availableTypes)));

        if ($reportTypes === []) {
            return $this->block(
                code: 'missing_tipos_relatorios',
                message: 'Selecione ao menos um relatorio para geracao automatica.',
                reason: 'Nenhum relatorio selecionado.',
            );
        }

        if ($invalidTypes !== []) {
            return $this->block(
                code: 'invalid_tipos_relatorios',
                message: 'Um ou mais tipos de relatorio configurados nao estao disponiveis.',
                reason: 'Tipos de relatorio invalidos: ' . implode(', ', $invalidTypes),
                meta: ['invalid_report_types' => $invalidTypes],
            );
        }

        $recipients = $this->resolveRecipients($autoConfig);

        if ($recipients->isEmpty()) {
            return $this->block(
                code: 'missing_destinatarios_relatorios',
                message: 'Selecione responsaveis ou usuarios adicionais com e-mail valido para receber os relatorios.',
                reason: 'Nenhum destinatario com e-mail valido foi encontrado.',
            );
        }

        $reports = collect($reportTypes)
            ->map(fn(string $reportType): array => $this->generateReport(
                curso: $curso,
                reportType: $reportType,
                label: $availableTypes[$reportType],
            ))
            ->values()
            ->all();

        $batchId = (string) Str::uuid();
        $storedReports = $this->storeReports($curso, $batchId, $reports);
        $reportsUrl = route('reports.automaticos.show', [
            'curso' => $curso->id,
            'batch' => $batchId,
        ]);

        $recipientIds = $recipients->pluck('id')
            ->map(fn(mixed $id): int => (int) $id)
            ->values()
            ->all();
        $reportLabels = collect($reports)->pluck('label')->values()->all();

        $this->notificationService->notifyReportsGenerated(
            curso: $curso,
            recipientIds: $recipientIds,
            reportsUrl: $reportsUrl,
            reportsCount: count($reports),
            reportLabels: $reportLabels,
        );

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(new RelatoriosAutomaticosMail(
                cursoTitulo: (string) $curso->titulo,
                reports: $reports,
            ));
        }

        return [
            'step' => [
                'status' => AutoStepStatus::COMPLETED->value,
                'completed' => true,
                'completed_at' => now(),
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => ['Relatorios gerados e enviados aos destinatarios configurados.'],
                ],
            ],
            'meta' => [
                'action' => 'generated_and_sent_reports',
                'processed_at' => now(),
                'report_types' => $reportTypes,
                'report_labels' => $reportLabels,
                'reports_count' => count($reports),
                'recipients_count' => $recipients->count(),
                'recipient_ids' => $recipientIds,
                'recipient_emails' => $recipients->pluck('email')->values()->all(),
                'filenames' => collect($reports)->pluck('filename')->values()->all(),
                'report_batch_id' => $batchId,
                'report_url' => $reportsUrl,
                'files' => $storedReports,
            ],
            'should_reanalyze' => true,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function resolveRecipients(array $autoConfig)
    {
        $responsibleIds = $this->booleanValue(
            data_get($autoConfig, 'relatorios.destinatarios.enviar_responsaveis'),
            true,
        )
            ? (array) data_get($autoConfig, 'notificacao.responsaveis.list', [])
            : [];

        $additionalIds = (array) data_get($autoConfig, 'relatorios.destinatarios.usuarios', []);
        $ids = collect([...$responsibleIds, ...$additionalIds])
            ->filter(fn(mixed $id): bool => filled($id))
            ->map(fn(mixed $id): int => (int) $id)
            ->unique()
            ->values();

        return User::query()
            ->whereIn('id', $ids->all())
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get()
            ->filter(fn(User $user): bool => filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false)
            ->values();
    }

    /**
     * @return array{filename: string, content_base64: string, label: string}
     */
    private function generateReport(Cursos $curso, string $reportType, string $label): array
    {
        $filters = [
            'report_type' => $reportType,
            'cursos_ids' => [$curso->id],
            'data_inicio' => $curso->inicio?->toDateString(),
            'data_fim' => $curso->termino?->toDateString(),
            'ativo' => '1',
        ];

        $request = Request::create('/relatorios/projeto-pedagogico', 'POST', $filters);
        $response = app(ReportProjetoPedagogicoController::class)->generate($request);
        $content = $this->responseToPdf($response);

        if ($content === '') {
            throw new \RuntimeException("O relatorio {$reportType} nao produziu um arquivo PDF.");
        }

        return [
            'filename' => 'relatorio-' . Str::slug($reportType) . '-curso-' . $curso->id . '.pdf',
            // O payload da fila precisa permanecer em UTF-8; os bytes do PDF ficam codificados.
            'content_base64' => base64_encode($content),
            'label' => $label,
        ];
    }

    private function responseToPdf(mixed $response): string
    {
        if ($response instanceof View) {
            return Pdf::loadHTML($response->render())
                ->setPaper('a4', 'landscape')
                ->output();
        }

        if ($response instanceof Response) {
            return (string) $response->getContent();
        }

        throw new \RuntimeException('Resposta inesperada ao gerar relatorio automatico.');
    }

    /**
     * @param  array<int, array{filename: string, content_base64: string, label: string}>  $reports
     * @return array<int, array{index: int, filename: string, label: string, path: string}>
     */
    private function storeReports(Cursos $curso, string $batchId, array $reports): array
    {
        $directory = "auto/reports/{$curso->id}/{$batchId}";

        return collect($reports)
            ->values()
            ->map(function (array $report, int $index) use ($directory): array {
                $content = base64_decode($report['content_base64'], true);

                if ($content === false || $content === '') {
                    throw new \RuntimeException("O relatorio {$report['filename']} possui conteudo invalido.");
                }

                $path = "{$directory}/{$report['filename']}";

                if (! Storage::disk('local')->put($path, $content)) {
                    throw new \RuntimeException("Nao foi possivel armazenar o relatorio {$report['filename']}.");
                }

                return [
                    'index' => $index,
                    'filename' => $report['filename'],
                    'label' => $report['label'],
                    'path' => $path,
                ];
            })
            ->all();
    }

    private function skip(string $reason): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::PENDING->value,
                'completed' => false,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'meta' => [
                'action' => 'skipped_generate_reports',
                'reason' => $reason,
                'skipped_at' => now(),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function block(string $code, string $message, string $reason, array $meta = []): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::AWAITING_ACTION->value,
                'completed' => false,
                'awaiting_action' => true,
                'analysis' => [
                    'blocked' => true,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'pending_actions' => [
                AutoStructureBuilder::makePendingAction(
                    code: $code,
                    message: $message,
                    step: self::stepKey(),
                    blocking: true,
                    severity: AutoSeverity::WARNING->value,
                ),
            ],
            'meta' => array_merge([
                'action' => 'blocked_generate_reports',
                'reason' => $reason,
                'blocked_at' => now(),
            ], $meta),
            'should_reanalyze' => true,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            $value = filled($value) ? [$value] : [];
        }

        return collect($value)
            ->filter(fn(mixed $id): bool => filled($id))
            ->map(fn(mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function booleanValue(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }
}
