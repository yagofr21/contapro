<?php

namespace App\Modules\ImportExport\Support;

use DateTimeImmutable;
use InvalidArgumentException;

class StrictDateParser
{
    public function parse(string $value): string
    {
        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException('Data invalida. Use AAAA-MM-DD ou DD/MM/AAAA.');
    }
}
