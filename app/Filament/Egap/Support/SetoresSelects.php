<?php

namespace App\Filament\Egap\Support;

use App\Models\egap\Cadastro\Setores;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class SetoresSelects
{
    public const EGAP_UNIDADES_ORGANIZACIONAIS = 'organizacionais';

    public const EGAP_UNIDADES_INVENTARIAVEIS = 'inventariaveis';

    public const EGAP_UNIDADES_DISTINTAS = 'distintas';

    public static function unidade(
        string $name = 'unidade_judiciaria',
        string $setorField = 'setor_id',
    ): Select {
        return self::unidadeBase($name, $setorField)
            ->options(fn (): array => self::unidadesEmesOptions());
    }

    public static function setor(
        string $name = 'setor_id',
        string $unidadeField = 'unidade_judiciaria',
    ): Select {
        return self::setorBase($name, $unidadeField)
            ->options(fn (Get $get): array => self::setoresEmesOptions($get($unidadeField)));
    }

    public static function unidadeEgap(
        string $name = 'UnidadeJudiciaria',
        string $setorField = 'Setor',
        string $scope = self::EGAP_UNIDADES_ORGANIZACIONAIS,
        string $optionLabel = 'unidadeorganizacional',
        string $optionKey = 'codigopai',
    ): Select {
        return self::unidadeBase($name, $setorField)
            ->options(fn (): array => self::unidadesEgapOptions($scope, $optionLabel, $optionKey));
    }

    public static function setorEgap(
        string $name = 'Setor',
        string $unidadeField = 'UnidadeJudiciaria',
    ): Select {
        return self::setorBase($name, $unidadeField)
            ->options(fn (Get $get): array => self::setoresEgapOptions($get($unidadeField)));
    }

    private static function unidadeBase(string $name, string $setorField): Select
    {
        return Select::make($name)
            ->label('Unidade Judiciária')
            ->required()
            ->searchable()
            ->preload()
            ->live()
            ->native(false)
            ->placeholder('Selecione a unidade judiciária')
            ->afterStateUpdated(fn (Set $set) => $set($setorField, null));
    }

    private static function setorBase(string $name, string $unidadeField): Select
    {
        return Select::make($name)
            ->label('Setor')
            ->required()
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder(fn (Get $get): string => blank($get($unidadeField))
                ? 'Selecione primeiro a unidade judiciária'
                : 'Selecione o setor')
            ->disabled(fn (Get $get): bool => blank($get($unidadeField)));
    }

    private static function unidadesEmesOptions(): array
    {
        return Setores::query()
            ->whereNotNull('codigopai')
            ->selectRaw('codigopai, MIN(unidadeorganizacional) as unidadeorganizacional')
            ->groupBy('codigopai')
            ->orderByRaw('MIN(unidadeorganizacional)')
            ->pluck('unidadeorganizacional', 'codigopai')
            ->toArray();
    }

    private static function setoresEmesOptions(mixed $codigoPai): array
    {
        if (blank($codigoPai)) {
            return [];
        }

        return Setores::query()
            ->where('codigopai', $codigoPai)
            ->whereColumn('id', '<>', 'codigopai')
            ->orderBy('setor')
            ->pluck('setor', 'id')
            ->toArray();
    }

    private static function unidadesEgapOptions(string $scope, string $optionLabel, string $optionKey): array
    {
        $query = Setores::query();

        match ($scope) {
            self::EGAP_UNIDADES_ORGANIZACIONAIS => $query->whereColumn('id', 'codigodauo'),
            self::EGAP_UNIDADES_INVENTARIAVEIS => $query->whereColumn('id', 'codigopai'),
            self::EGAP_UNIDADES_DISTINTAS => $query
                ->whereNotNull('codigopai')
                ->select('codigopai', 'unidadeorganizacional', 'ordem')
                ->distinct('codigopai'),
            default => throw new \InvalidArgumentException("Escopo de unidades EGAP inválido: {$scope}"),
        };

        return $query
            ->orderBy($scope === self::EGAP_UNIDADES_DISTINTAS ? 'ordem' : $optionLabel)
            ->pluck($optionLabel, $optionKey)
            ->toArray();
    }

    private static function setoresEgapOptions(mixed $codigoPai): array
    {
        if (blank($codigoPai)) {
            return [];
        }

        return Setores::query()
            ->where('codigopai', $codigoPai)
            ->orderBy('setor')
            ->pluck('setor', 'id')
            ->toArray();
    }
}
