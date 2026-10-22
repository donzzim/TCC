<?php

namespace App\Filament\Exports;


use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;
use BackedEnum;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoFrequencias;
use Illuminate\Container\Container;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Model;

class EscolaCursoFrequenciasExporter extends Exporter
{
    protected static ?string $model = CursoFrequencias::class;

    public function __invoke(Model $record): array
    {
        $this->record = $record;

        $columns = $this->getCachedColumns();

        $data = [];

        foreach (array_keys($this->columnMap) as $column) {
            // monta array associativo com a chave IGUAL ao columnMap
            $data[$column] = $columns[$column]->getFormattedState();
        }

        return $data;
    }


    // public function __construct(Export $export, array $columnMap, array $options)
    // {
    //     parent::__construct($export, $columnMap, $options);

    //     // Intercepta e reescreve getFileDirectory NO MOMENTO DA CRIAÇÃO
    //     Container::getInstance()->bindMethod(
    //         [$export, 'getFileDirectory'],
    //         function () {
    //             return ''; // salva direto no root do disk
    //         }
    //     );
    // }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('aluno.name')
                ->label('Nome do Aluno'),


            ExportColumn::make('aluno.cpf')
                ->formatStateUsing(fn($record) => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $record->aluno->cpf))
                ->label('CPF do Aluno'),


            ExportColumn::make('aluno.email')
                ->label('Email do Aluno'),

            ExportColumn::make('aluno.telefone')
                ->label('Telefone do Aluno'),

            ExportColumn::make('aluno.perfil.tipopessoa.descricao')
                ->label('Vinculo'),


            ExportColumn::make('pcd')
                ->formatStateUsing(function ($record) {

                    if (isset($record->aluno->perfil->pcd) && is_array($record->aluno->perfil->pcd) && count($record->aluno->perfil->pcd) > 0) {
                        return 'SIM';
                    }
                    return 'NÃO';
                })
                ->label('PCD'),


            ExportColumn::make('titulo')
                ->formatStateUsing(function ($record) {
                    $acao = $record->curso ?? $record->atividade;
                    if ($acao->curso_id) {
                        return $acao->curso->titulo . ' - ' . $acao->titulo;
                    } else {
                        return $acao->titulo;
                    }
                })


                ->label('Curso/Atividade'),


            ExportColumn::make('data_frequencia')
                ->formatStateUsing(function ($record) {
                    $data = $record->data_frequencia ?? $record->created_at;
                    return date('d/m/Y H:i:s', strtotime($data));
                })
                ->label('Data da frequencia'),

            ExportColumn::make('data_registro')
                ->formatStateUsing(function ($record) {
                    if ($record->situacaofrequencia_id == config('settings.frequencia.presente')) {
                        $data = $record->updated_at ?? $record->created_at;
                        return date('d/m/Y H:i:s', strtotime($data));
                    } else {
                        return 'N/A';
                    }
                })
                ->label('Data de registro'),


            ExportColumn::make('situacaofrequencia_id')
                ->formatStateUsing(function ($record) {
                    $presente = $record->situacaofrequencia_id == config('settings.frequencia.presente') ? 'SIM' : 'NÃO';
                    return $presente;
                })
                ->label('Presente'),


            ExportColumn::make('aluno.setorPrincipal.setor')
                ->label('Lotação')


        ];
    }

    public function getFormats(): array
    {
        return [
            // ExportFormat::Csv,
            ExportFormat::Xlsx
        ];
    }

    public function getFileDisk(): string
    {
        return 'local';
    }



    public function getJobQueue(): ?string
    {
        return null;
    }

    public function getJobConnection(): ?string
    {
        return null;
    }

    public function getJobMiddleware(): array
    {
        return []; // impede uso de WithoutOverlapping
    }

    public function getJobBatchName(): ?string
    {
        return null;
    }



    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Exportação concluida.';
    }
}
