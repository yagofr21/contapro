<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ResourceBundle;

class ExampleTest extends TestCase
{
    public function test_that_basic_arithmetic_works(): void
    {
        $this->assertSame(4, 2 + 2);
    }

    public function test_that_pt_br_locale_is_available_on_the_system(): void
    {
        $locales = ResourceBundle::getLocales('');

        $this->assertContains('pt_BR', $locales);
    }
}
