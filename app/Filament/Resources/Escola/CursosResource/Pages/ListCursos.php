<?php

namespace App\Filament\Resources\Escola\CursosResource\Pages;

use Illuminate\Support\Facades\DB;
use App\Models\Escola\Cursos;
use App\Models\Escola\CursoArquivos;
use Filament\Notifications\Notification;
use App\Filament\Resources\Escola\CursosResource;
use App\Models\Escola\CursoFrequencias;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class ListCursos extends ListRecords
{
    protected static string $resource = CursosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('Novo Evento')
            //     ->label('Novo Evento')
            //     ->form(
            //         CursosResource::getFormfieldsEvento()
            //     )
            //     ->action(function (array $data) {

            //     }),

            Actions\CreateAction::make()
                ->label('Novo'),

            Actions\Action::make('curso_ead')
                ->label('Novo Curso EaD')
                ->form(
                    CursosResource::getFormfieldsEad()
                )
                ->action(function (array $data) {

                    DB::transaction(function () use ($data) {
                        $arquivosData = $data['arquivos'] ?? [];
                        $publicoIds = $data['publico'] ?? [];

                        unset($data['arquivos']);
                        unset($data['publico']);

                        $curso = Cursos::create($data);
                        if (!empty($arquivosData)) {
                            foreach ($arquivosData as $item) {
                                if (!empty($item['arquivo'])) {
                                    CursoArquivos::create([
                                        'curso_id'  => $curso->id,
                                        'descricao' => $item['descricao'],
                                        'arquivo'   => $item['arquivo'],
                                    ]);
                                }
                            }
                        }
                        if (!empty($publicoIds)) {
                            $curso->publico()->sync($publicoIds);
                        }

                        Notification::make()
                            ->title('Curso EaD criado com sucesso!')
                            ->success()
                            ->send();
                    });
                })
                ->modalWidth('7xl'),
        ];
    }

    public function salvarJustificativa($frequenciaId, $justificativa)
    {
        $frequencia = CursoFrequencias::find($frequenciaId);

        if (!$frequencia) {
            Notification::make()
                ->title('Erro')
                ->body('Frequência não encontrada.')
                ->danger()
                ->send();
            return;
        }

        $frequencia->update([
            'situacaofrequencia_id' => config('settings.frequencia.presente'), // 2701
            'observacao' => $justificativa,
            'data_frequencia' => now(),
            'atualizado_por' => Auth::id(),
        ]);

        Notification::make()
            ->title('Justificativa registrada')
            ->body("Aluno {$frequencia->aluno->name} marcado como presente.")
            ->success()
            ->send();

        // Opcional: Se quiser recarregar a tabela principal (fundo)
        // $this->dispatch('refresh');
    }
}
