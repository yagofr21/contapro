<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_preserves_four_decimal_places(): void
    {
        $money = Money::fromDecimal('10.1', Currency::BRL);

        $this->assertSame('10.1000', $money->amount);
        $this->assertSame(Currency::BRL, $money->currency);
    }

    public function test_it_calculates_without_floating_point_errors(): void
    {
        $total = Money::fromDecimal('0.1000', Currency::BRL)
            ->add(Money::fromDecimal('0.2000', Currency::BRL))
            ->multiply('3');

        $this->assertSame('0.9000', $total->amount);
    }

    public function test_it_rounds_results_to_the_money_scale(): void
    {
        $result = Money::fromDecimal('10.0000', Currency::BRL)->divide('3');

        $this->assertSame('3.3333', $result->amount);
    }

    public function test_it_rejects_operations_between_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('10', Currency::BRL)->add(
            Money::fromDecimal('10', Currency::USD),
        );
    }
}
