<?php

namespace App\Filament\Egap\Support;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class MoneyInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->inputMode('decimal')
            ->prefix('R$')
            ->placeholder('0,00')
            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            ->formatStateUsing(fn($state): ?string => self::formatState($state))
            ->dehydrateStateUsing(fn($state): ?string => self::normalizeState($state))
            ->mutateStateForValidationUsing(fn($state): ?string => self::normalizeState($state))
            ->rules(['nullable', 'numeric', 'decimal:0,2']);
    }

    public static function formatState(float|int|string|null $state): ?string
    {
        $normalized = self::normalizeState($state);

        return $normalized === null
            ? null
            : number_format((float) $normalized, 2, ',', '.');
    }

    public static function normalizeState(float|int|string|null $state): ?string
    {
        if ($state === null || trim((string) $state) === '') {
            return null;
        }

        $value = preg_replace('/[^\d,.-]/', '', (string) $state) ?? '';

        if ($value === '') {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        $lastComma = $lastComma === false ? -1 : $lastComma;
        $lastDot = $lastDot === false ? -1 : $lastDot;

        if ($lastComma > -1 && $lastDot > -1) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
        } elseif ($lastComma > -1 || $lastDot > -1) {
            $separator = $lastComma > -1 ? ',' : '.';
            $separatorPosition = max($lastComma, $lastDot);
            $decimalDigits = strlen($value) - $separatorPosition - 1;
            $decimalSeparator = $decimalDigits > 0 && $decimalDigits <= 2 ? $separator : null;
        } else {
            $decimalSeparator = null;
        }

        if ($decimalSeparator === ',') {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($decimalSeparator === '.') {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace([',', '.'], '', $value);
        }

        return $value;
    }
}
