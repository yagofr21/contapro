<?php

namespace App\Modules\ImportExport\Support;

use InvalidArgumentException;

class DecimalParser
{
    public function parse(string $value, int $scale, int $maxIntegerDigits): string
    {
        $value = trim(str_replace("\u{00A0}", '', $value));

        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        }

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $value) !== 1) {
            throw new InvalidArgumentException('Numero invalido.');
        }

        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException("O numero aceita no maximo {$scale} casas decimais.");
        }

        $negative = str_starts_with($integer, '-');
        $integer = ltrim($integer, '-0') ?: '0';

        if (strlen($integer) > $maxIntegerDigits) {
            throw new InvalidArgumentException("O numero aceita no maximo {$maxIntegerDigits} digitos inteiros.");
        }

        $fraction = str_pad($fraction, $scale, '0');

        return ($negative && ($integer !== '0' || trim($fraction, '0') !== '') ? '-' : '')
            .$integer
            .($scale > 0 ? '.'.$fraction : '');
    }
}
