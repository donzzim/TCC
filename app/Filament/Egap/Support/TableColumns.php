<?php

namespace App\Filament\Egap\Support;

use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;

class TableColumns
{
    public static function updatedBy(
        string $name,
        ?string $label = 'Atualizado por',
        string $dateColumn = 'date_time',
        string $format = 'd/m/Y H:i',
    ): TextColumn {
        return self::text($name, $label)
            ->description(function ($record) use ($dateColumn, $format) {
                $value = self::nullIfZeroDate($record->{$dateColumn} ?? null);

                return filled($value) ? Carbon::parse($value)->format($format) : null;
            });
    }

    public static function text(string $name, ?string $label = null, bool $isFirstColumn = false, bool $isIdColumn = false): TextColumn
    {
        return self::base($name, $label, $isFirstColumn)
            ->badge($isIdColumn)
            ->default('-');
    }

    public static function date(
        string $name,
        ?string $label = null,
        string $format = 'd/m/Y',
        bool $isFirstColumn = false,
    ): TextColumn {
        return self::base($name, $label, $isFirstColumn)
            ->placeholder('-')
            ->state(fn ($record) => self::nullIfZeroDate(data_get($record, $name)))
            ->date($format);
    }

    public static function dateTime(
        string $name,
        ?string $label = null,
        ?string $format = 'd/m/Y H:i',
        bool $isFirstColumn = false,
    ): TextColumn {
        $column = self::base($name, $label, $isFirstColumn)
            ->placeholder('-')
            ->state(fn ($record) => self::nullIfZeroDate(data_get($record, $name)));

        return $format === null
            ? $column->dateTime()
            : $column->dateTime($format);
    }

    private static function nullIfZeroDate(mixed $value): mixed
    {
        if (blank($value)) {
            return null;
        }

        return str_starts_with((string) $value, '0000-00-00') ? null : $value;
    }

    public static function number(
        string $name,
        ?string $label = null,
        int $decimalPlaces = 0,
        bool $isFirstColumn = false,
    ): TextColumn {
        return self::base($name, $label, $isFirstColumn)
            ->placeholder('-')
            ->numeric($decimalPlaces, decimalSeparator: ',', thousandsSeparator: '.');
    }

    public static function money(
        string $name,
        ?string $label = null,
        string $currency = 'BRL',
        bool $divideBy = false,
        ?string $locale = null,
        bool $isFirstColumn = false,
    ): TextColumn {
        return self::base($name, $label, $isFirstColumn)
            ->placeholder('-')
            ->money($currency, $divideBy, $locale)
            ->badge()
            ->color('success');
    }

    private static function base(string $name, ?string $label = null, bool $isFirstColumn = false): TextColumn
    {
        $column = TextColumn::make($name)
            ->sortable()
            ->searchable();

        if (! $isFirstColumn) {
            $column->alignCenter();
        }

        if ($label !== null) {
            $column->label($label);
        }

        return $column;
    }
}
