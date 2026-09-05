<?php

namespace App\ValueObjects;

use App\Enums\Currency;
use InvalidArgumentException;
use NumberFormatter;

final readonly class Money
{
    private const int SCALE = 4;

    private function __construct(
        public string $amount,
        public Currency $currency,
    ) {}

    public static function fromDecimal(string|int $amount, Currency $currency): self
    {
        $value = (string) $amount;

        if (preg_match('/^-?\d+(\.\d{1,4})?$/', $value) !== 1) {
            throw new InvalidArgumentException('Money amounts must have at most four decimal places.');
        }

        return new self(bcadd($value, '0', self::SCALE), $currency);
    }

    public function add(self $money): self
    {
        $this->assertSameCurrency($money);

        return new self(bcadd($this->amount, $money->amount, self::SCALE), $this->currency);
    }

    public function subtract(self $money): self
    {
        $this->assertSameCurrency($money);

        return new self(bcsub($this->amount, $money->amount, self::SCALE), $this->currency);
    }

    public function multiply(string|int $multiplier): self
    {
        $value = self::assertNumeric($multiplier);

        return new self(bcround(bcmul($this->amount, $value, 8), self::SCALE), $this->currency);
    }

    public function divide(string|int $divisor): self
    {
        $value = self::assertNumeric($divisor);

        if (bccomp($value, '0', 8) === 0) {
            throw new InvalidArgumentException('Money cannot be divided by zero.');
        }

        return new self(bcround(bcdiv($this->amount, $value, 8), self::SCALE), $this->currency);
    }

    public function compareTo(self $money): int
    {
        $this->assertSameCurrency($money);

        return bccomp($this->amount, $money->amount, self::SCALE);
    }

    public function equals(self $money): bool
    {
        return $this->currency === $money->currency && $this->compareTo($money) === 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) < 0;
    }

    public function format(string $locale = 'pt_BR'): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        $formatted = $formatter->formatCurrency((float) $this->amount, $this->currency->value);

        if ($formatted === false) {
            throw new InvalidArgumentException('The money amount could not be formatted.');
        }

        return $formatted;
    }

    private static function assertNumeric(string|int $value): string
    {
        $numeric = (string) $value;

        if (preg_match('/^-?\d+(\.\d+)?$/', $numeric) !== 1) {
            throw new InvalidArgumentException('The provided value must be numeric.');
        }

        return $numeric;
    }

    private function assertSameCurrency(self $money): void
    {
        if ($this->currency !== $money->currency) {
            throw new InvalidArgumentException('Money operations require matching currencies.');
        }
    }
}
