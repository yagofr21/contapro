<?php

namespace App\Modules\Finance\Enums;

use Carbon\CarbonImmutable;

enum ScheduleFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * Advance a date to the next occurrence using this frequency.
     *
     * @param  CarbonImmutable  $date  The current reference date.
     */
    public function advance(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this) {
            self::Daily => $date->addDay(),
            self::Weekly => $date->addWeek(),
            self::Monthly => $date->addMonth(),
            self::Yearly => $date->addYear(),
        };
    }

    /**
     * Client-facing label in Brazilian Portuguese.
     */
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Diaria',
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensal',
            self::Yearly => 'Anual',
        };
    }

    /**
     * The frequencies supported by the auto-generation context.
     *
     * @return array<int, self>
     */
    public static function supported(): array
    {
        return [self::Daily, self::Weekly, self::Monthly, self::Yearly];
    }
}
