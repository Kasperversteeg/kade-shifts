<?php

namespace Tests\Unit;

use App\Models\TimeEntry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimeEntryCalculationTest extends TestCase
{
    #[Test]
    public function standard_day_shift(): void
    {
        $result = TimeEntry::calculateTotalHours('09:00', '17:00', 30);

        $this->assertEquals(7.5, $result);
    }

    #[Test]
    public function no_break(): void
    {
        $result = TimeEntry::calculateTotalHours('09:00', '17:00', 0);

        $this->assertEquals(8.0, $result);
    }

    #[Test]
    public function cross_midnight_shift(): void
    {
        $result = TimeEntry::calculateTotalHours('22:00', '06:00', 0);

        $this->assertEquals(8.0, $result);
    }

    #[Test]
    public function cross_midnight_with_break(): void
    {
        $result = TimeEntry::calculateTotalHours('22:00', '06:00', 30);

        $this->assertEquals(7.5, $result);
    }

    #[Test]
    public function short_shift(): void
    {
        $result = TimeEntry::calculateTotalHours('09:00', '13:00', 0);

        $this->assertEquals(4.0, $result);
    }

    #[Test]
    public function full_day_with_break(): void
    {
        $result = TimeEntry::calculateTotalHours('08:00', '20:00', 60);

        $this->assertEquals(11.0, $result);
    }
}
