<?php

namespace Tests\Feature\MarketData;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class MarketDataScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_data_sync_is_scheduled_three_times_a_day(): void
    {
        $this->artisan('schedule:list');

        $syncEvent = collect(Schedule::events())->first(
            fn ($event): bool => str_contains((string) $event->command, 'market-data:sync'),
        );

        $this->assertNotNull($syncEvent);
        $this->assertSame('0 7,12,18 * * *', $syncEvent->expression);
    }
}
