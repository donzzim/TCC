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
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Instrutores;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use Illuminate\Container\Container;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Model;

class EscolaCursoInstrutoresExporter extends Exporter
{
    protected static ?string $model = Instrutores::class;

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
            ExportColumn::make('instrutor.name')
                ->label('Nome do instrutor'),


            ExportColumn::make('instrutor.cpf')
                ->formatStateUsing(fn($record) => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $record->instrutor->cpf))
                ->label('CPF do instrutor'),


            ExportColumn::make('instrutor.email')
                ->label('Email do instrutor'),

            ExportColumn::make('instrutor.telefone')
                ->label('Telefone do instrutor'),

            ExportColumn::make('instrutor.perfil.tipopessoa.descricao')
                ->label('Vinculo'),

            ExportColumn::make('instrutor.perfil.sexo')
                ->formatStateUsing(function ($record) {
                    $sexo = $record->instrutor->perfil->sexo ?? null;
                    if (!$sexo) {
                        return 'N/A';
                    }
                    return TipoSexo::where('id', $sexo)->value('descricao') ?? 'N/A';
                })
                ->label('Sexo'),

            ExportColumn::make('curso.inicio')
                ->formatStateUsing(function ($record) {
                    $inicio = $record->curso->inicio ?? null;
                    if (!$inicio) {
                        return 'N/A';
                    }
                    return date('d/m/Y', strtotime($inicio));
                })
                ->label('Data do curso'),

            ExportColumn::make('instrutor.perfil.raca')
                ->formatStateUsing(function ($record) {
                    $raca = $record->instrutor->perfil->raca ?? null;
                    if (!$raca) {
                        return 'N/A';
                    }
                    return TipoRaca::where('id', $raca)->value('descricao') ?? 'N/A';
                })
                ->label('Raça/Cor'),


            ExportColumn::make('pcd')
                ->formatStateUsing(function ($record) {

                    if (isset($record->instrutor->perfil->pcd) && is_array($record->instrutor->perfil->pcd) && count($record->instrutor->perfil->pcd) > 0) {
                        return 'SIM';
                    }
                    return 'NÃO';
                })
                ->label('PCD'),


            ExportColumn::make('titulo')
                ->formatStateUsing(function ($record) {
                    $acao = $record->curso ?? null;
                    if (!$acao) {
                        return 'N/A';
                    }
                    if ($acao->curso_id) {
                        return $acao->curso->titulo . ' - ' . $acao->titulo;
                    } else {
                        return $acao->titulo;
                    }
                })


                ->label('Curso/Atividade'),


            ExportColumn::make('instrutor.setorPrincipal.setor')
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
