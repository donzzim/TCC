<?php

namespace App\Filament\Exports;

use Filament\Actions\Action;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Filament\Actions\ExportAction as TableExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SynchronousExportAction extends TableExportAction
{
    use \Filament\Actions\Concerns\CanExportRecords {
        setUp as protected parentSetUp;
    }

    protected function setUp(): void
    {
        $this->parentSetUp();

        // O closure não deve ter type-hint no $action para evitar mismatch
        $this->action(function ($action, array $data, Component $livewire) {
            $exporterClass = $action->getExporter();

            // --- QUERY ----
            if ($livewire instanceof HasTable) {
                $query = $livewire->getTableQueryForExport();
            } else {
                $query = $exporterClass::getModel()::query();
            }

            $query = $exporterClass::modifyQuery($query);

            $options = array_merge(
                $action->getOptions(),
                Arr::except($data, ['columnMap']),
            );

            if ($this->modifyQueryUsing) {
                $query = $this->evaluate($this->modifyQueryUsing, [
                    'query'   => $query,
                    'options' => $options,
                ]) ?? $query;
            }

            $records = $action instanceof ExportBulkAction ? $action->getRecords() : null;

            $totalRows = $records ? $records->count() : $query->toBase()->getCountForPagination();
            $maxRows   = $action->getMaxRows() ?? $totalRows;

            if ($maxRows < $totalRows) {
                Notification::make()
                    ->title(__('filament-actions::export.notifications.max_rows.title'))
                    ->body(trans_choice('filament-actions::export.notifications.max_rows.body', $maxRows, [
                        'count' => Number::format($maxRows),
                    ]))
                    ->danger()
                    ->send();
                return;
            }

            // --- USER ---
            $user = Auth::user();

            // --- COLUMN MAP ---
            if ($action->hasColumnMapping()) {
                $columnMap = collect($data['columnMap'])
                    ->dot()
                    ->reduce(fn(Collection $carry, $value, string $key): Collection =>
                    $carry->mergeRecursive([
                        Str::beforeLast($key, '.') => [
                            Str::afterLast($key, '.') => $value,
                        ],
                    ]), collect())
                    ->filter(fn(array $column): bool => $column['isEnabled'] ?? false)
                    ->mapWithKeys(fn(array $column, string $name) => [$name => $column['label']])
                    ->all();
            } else {
                $columnMap = collect($exporterClass::getColumns())
                    ->mapWithKeys(fn($column) => [$column->getName() => $column->getLabel()])
                    ->all();
            }

            // --- EXPORT MODEL ---
            /** @var Export $export */
            $export = app(Export::class);
            $export->user()->associate($user);
            $export->exporter = $exporterClass;
            $export->total_rows = $totalRows;

            // Aqui instanciamos o exporter corretamente (não chamar métodos não estáticos)
            $exporter = $export->getExporter(
                columnMap: $columnMap,
                options: $options,
            );

            // Nome base gerado pelo exporter (agora via instância)
            $baseFileName = $action->getFileName($export) ?? $exporter->getFileName($export);

            $safeName = Str::slug(pathinfo($baseFileName, PATHINFO_FILENAME));
            $fileName = "{$safeName}-{$export->getKey()}.xlsx";

            // disk local e path em storage/app/private
            $diskName = 'local';
            $export->file_disk = $diskName;
            $export->file_name = $fileName;
            $export->save();

            $export = Export::latest()->first();
            $export->file_name = $fileName = "{$safeName}-{$export->getKey()}.xlsx";
            $export->save();


            // -------------------------------------------------------
            //  XLSX (PhpSpreadsheet) — SÍNCRONO, SEM QUEUE
            // -------------------------------------------------------
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // HEADER
            $colIndex = 1;
            foreach (array_values($columnMap) as $label) {
                $sheet->setCellValueByColumnAndRow($colIndex++, 1, $label);
            }

            // LINHAS
            $rowIndex = 2;

            // Função que adiciona uma linha - usa a instância $exporter criada acima
            $addRow = function ($row) use (&$rowIndex, $sheet, $columnMap, $exporter) {
                $rowArray = $exporter($row);

                $col = 1;
                foreach (array_keys($columnMap) as $colKey) {
                    $value = $rowArray[$colKey] ?? null;
                    $sheet->setCellValueByColumnAndRow($col++, $rowIndex, $value);
                }

                $rowIndex++;
            };

            if ($records) {
                // Exportação via seleção manual
                foreach ($records as $rec) {
                    $addRow($rec);
                }
            } else {
                // Exportação da query em chunks para evitar OOM
                $query->chunk(500, function ($rows) use ($addRow) {
                    foreach ($rows as $row) {
                        $addRow($row);
                    }
                });
            }

            // GERAR ARQUIVO EM local storage path: storage/app/private/{fileName}
            $writer = new Xlsx($spreadsheet);

            // Garantir pasta existe
            $privatePath = storage_path('app/private');
            if (! is_dir($privatePath)) {
                mkdir($privatePath, 0755, true);
            }

            $tempPath = storage_path("app/private/{$fileName}");
            $writer->save($tempPath);

            // Se seu disk 'local' não aponta exatamente para storage/app, você pode mover:
            // Storage::disk($diskName)->put($fileName, file_get_contents($tempPath));
            // unlink($tempPath);

            // MARCAR COMO COMPLETO
            $export->completed_at = now();
            $export->save();

            // LINK DE DOWNLOAD (rota que você já possua; adapte se necessário)
            // eu coloquei /exports/download/{id}/{filename} para evitar confusão com diretórios
            $url = url("/exports/download/{$export->file_name}");

            Notification::make()
                ->title('Exportação concluída')
                ->body("Arquivo XLSX gerado com sucesso.")
                ->actions([
                    Action::make('download')
                        ->label('Baixar XLSX')
                        ->url($url)
                        ->openUrlInNewTab(),
                ])
                ->success()
                ->persistent()
                ->send();
        });
    }
}
