<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard;

use App\Services\Egap\PatrimonioDashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Number;

class PatrimonioTopMateriaisValorTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $service = app(PatrimonioDashboardService::class);

        return $table
            ->heading('Top 10 materiais por valor')
            ->description('Bens móveis ativos agrupados por descrição resumida e detalhada')
            ->records(fn() => $service->getTopMovableMaterials($this->pageFilters))
            ->striped()
            ->paginated(false)
            ->defaultSort('valor_atual', 'desc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('descricao_resumida')
                    ->label('Material')
                    ->searchable(false)
                    ->wrap(),

                TextColumn::make('descricao_detalhada')
                    ->label('Descrição detalhada')
                    ->searchable(false)
                    ->wrap(),

                TextColumn::make('quantidade')
                    ->label('Qtde')
                    ->alignEnd()
                    ->formatStateUsing(fn($state): string => Number::format((int) $state, locale: 'pt_BR')),

                TextColumn::make('valor_aquisicao')
                    ->label('Valor aquisição')
                    ->money('BRL', locale: 'pt_BR')
                    ->alignEnd()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('valor_atual')
                    ->label('Valor atual')
                    ->money('BRL', locale: 'pt_BR')
                    ->alignEnd()
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->selectable(false);
    }
}
