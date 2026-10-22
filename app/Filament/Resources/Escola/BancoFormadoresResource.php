<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Escola\ProjetoPedagogicoCluster;
use App\Filament\Resources\Escola\BancoFormadoresResource\Pages;
use App\Filament\Resources\BancoFormadoresResource\RelationManagers;
use App\Models\BancoFormadores;
use App\Models\Perfil;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class BancoFormadoresResource extends Resource
{
    protected static ?string $model = Perfil::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $recordTitleAttribute = 'user.name';
    protected static ?string $modelLabel = 'Banco De Formadores';
    protected static ?string $navigationLabel = 'Banco de Formadores ';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('user.avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/admin-tie.png'))
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Nome')
                    ->getStateUsing(
                        function ($record) {
                            if ($record->estrangeiro) {
                                $badge = '<span title="Perfil estrangeiro" class="inline-flex items-center px-2 py-0.5 ml-2 rounded text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">Estrangeiro</span>';
                                return new HtmlString($record->user->name . ' ' . $badge);
                            }
                            return $record->user->name;
                        }
                    ),

                Tables\Columns\TextColumn::make('cargo'),
                Tables\Columns\TextColumn::make('titulacao')
                    ->label('Titulações')
                    ->state(function ($record) {
                        $bachareis = 0;
                        $doutorados = 0;
                        $mestrados = 0;
                        $tecnologos = 0;
                        $especializacoes = 0;
                        if (isset($record->titulacao)) {
                            $bachareis = count(array_filter($record->titulacao, fn($titulo) => $titulo === 'Bacharel'));
                            $bachareis = count(array_filter($record->titulacao, fn($titulo) => $titulo === 'Doutor'));
                            $mestrados = count(array_filter($record->titulacao, fn($titulo) => $titulo === 'Mestre'));
                            $tecnologos = count(array_filter($record->titulacao, fn($titulo) => $titulo === 'Técnico'));
                            $especializacoes = count(array_filter($record->titulacao, fn($titulo) => $titulo === 'Especializado'));
                        }

                        $html = '';
                        if ($bachareis) {
                            $html .= '<div class="flex">';

                            $badge = '<div class="flex"><span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-500 text-green-500 bg-green-100 rounded-md dark:bg-green-800 dark:text-green-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-green-200 rounded-full text-sky-900 ms-2">{count}</span></span></div>';
                            $html .= str_replace(['{messages}', '{count}'], [
                                'Bacharelados',
                                $bachareis
                            ], $badge);
                        }
                        if ($doutorados) {
                            $html .= '<div class="flex">';

                            $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-purple-300 dark:ring-purple-700 text-purple-500 bg-purple-50 rounded-md dark:bg-purple-800 dark:text-purple-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-purple-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                            $html .= str_replace(['{messages}', '{count}'], [
                                'Doutorados',
                                $doutorados
                            ], $badge);
                            $html .= '</div>';
                        }

                        if ($mestrados) {

                            $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-yellow-300 dark:ring-yellow-700 text-yellow-500 bg-yellow-50 rounded-md dark:bg-yellow-800 dark:text-yellow-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-yellow-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                            $html .= str_replace(['{messages}', '{count}'], [
                                'Mestrados',
                                $mestrados
                            ], $badge);

                            $html .= '</div>';
                        }

                        if ($tecnologos) {
                            $html .= '<div class="flex">';

                            $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-sky-300 dark:ring-sky-700 text-sky-500 bg-sky-50 rounded-md dark:bg-sky-800 dark:text-sky-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold rounded-full text-sky-900 bg-sky-200 ms-2">{count}</span></span>';
                            $html .= str_replace(['{messages}', '{count}'], [
                                'Tecnólogos',
                                $tecnologos
                            ], $badge);
                        }

                        if ($especializacoes) {
                            $html .= '<div class="flex">';

                            $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-red-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                            $html .= str_replace(['{messages}', '{count}'], [
                                'Especializações',
                                $especializacoes
                            ], $badge);

                            $html .= '</div>';
                        }
                        return new HtmlString($html);
                    }),
                Tables\Columns\TextColumn::make('nivel')
                    ->label('Nivel')
                    ->state(function ($record) {
                        $nivel = (int) $record->nivel;
                        $labels = [
                            0 => ['label' => 'NÃ­vel 0', 'color' => 'gray'],
                            1 => ['label' => 'NÃ­vel 1', 'color' => 'green'],
                            2 => ['label' => 'NÃ­vel 2', 'color' => 'blue'],
                            3 => ['label' => 'NÃ­vel 3', 'color' => 'orange'],
                            4 => ['label' => 'NÃ­vel 4', 'color' => 'purple'],
                            5 => ['label' => 'NÃ­vel 5', 'color' => 'yellow'],
                        ];
                        $data = $labels[$nivel] ?? $labels[0];

                        $colorClasses = [
                            'gray' => 'bg-gray-100 text-gray-800 ring-gray-300',
                            'green' => 'bg-green-100 text-green-800 ring-green-300',
                            'blue' => 'bg-blue-100 text-blue-800 ring-blue-300',
                            'purple' => 'bg-purple-100 text-purple-800 ring-purple-300',
                            'orange' => 'bg-orange-100 text-orange-800 ring-orange-300',
                            'yellow' => 'bg-yellow-100 text-yellow-800 ring-yellow-300',
                        ];

                        $badgeClass = $colorClasses[$data['color']] ?? $colorClasses['gray'];

                        return new HtmlString(
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ring-1 ' . $badgeClass . '">' .
                                $data['label'] .
                                '</span>'
                        );
                    })
                    ->html(),

            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    ->preload()
                    ->attribute('user_id')
                    ->columnSpan(6),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),
            ])
            ->toolBarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->persistFiltersInSession()
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBancoFormadores::route('/'),
            'create' => Pages\CreateBancoFormadores::route('/create'),
            'edit' => Pages\EditBancoFormadores::route('/{record}/edit'),
        ];
    }
}
