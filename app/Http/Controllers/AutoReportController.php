<?php

namespace App\Http\Controllers;

use App\Models\Escola\Cursos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AutoReportController extends Controller
{
    public function show(Request $request, Cursos $curso, string $batch): View
    {
        $reports = $this->reportsForUser($request, $curso, $batch);
        $meta = data_get($curso->auto_steps, 'gerar_relatorios.meta', []);

        return view('reports.automaticos', [
            'curso' => $curso,
            'reports' => $reports,
            'generatedAt' => data_get($meta, 'processed_at'),
        ]);
    }

    public function download(Request $request, Cursos $curso, string $batch, int $report): mixed
    {
        $reports = $this->reportsForUser($request, $curso, $batch);
        $file = collect($reports)->firstWhere('index', $report);

        abort_unless(is_array($file), 404);
        abort_unless(Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download(
            $file['path'],
            $file['filename'],
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return array<int, array{index: int, filename: string, label: string, path: string}>
     */
    private function reportsForUser(Request $request, Cursos $curso, string $batch): array
    {
        abort_unless($request->user() && Auth::check(), 401);

        $meta = data_get($curso->auto_steps, 'gerar_relatorios.meta', []);
        abort_unless((string) data_get($meta, 'report_batch_id') === $batch, 404);

        $recipientIds = collect(data_get($meta, 'recipient_ids', []))
            ->map(fn(mixed $id): string => (string) $id)
            ->all();

        abort_unless(in_array((string) Auth::id(), $recipientIds, true), 403);

        return collect(data_get($meta, 'files', []))
            ->filter(fn(mixed $file): bool => is_array($file) && filled($file['path'] ?? null))
            ->values()
            ->all();
    }
}
