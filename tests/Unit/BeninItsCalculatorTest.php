<?php

namespace Tests\Unit;

use App\Payroll\BeninItsCalculator;
use PHPUnit\Framework\TestCase;

class BeninItsCalculatorTest extends TestCase
{
    private BeninItsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BeninItsCalculator;
    }

    public function test_first_bracket_is_exempt_including_the_srtb_surcharge(): void
    {
        $april = $this->calculator->calculate(60_000, 4);
        $march = $this->calculator->calculate(60_000, 3);
        $june = $this->calculator->calculate(60_000, 6);

        $this->assertSame(0, $april['its']);
        $this->assertSame(0, $march['srtb']);
        $this->assertSame(0, $june['srtb']);
        $this->assertSame(0, $june['its']);
    }

    public function test_official_example_of_500000_francs_is_71500(): void
    {
        $result = $this->calculator->calculate(500_000, 4);

        $this->assertSame(71_500, $result['scale_tax']);
        $this->assertSame(0, $result['srtb']);
        $this->assertSame(71_500, $result['its']);
    }

    public function test_march_and_june_add_the_ortb_surcharge_above_the_first_bracket(): void
    {
        $march = $this->calculator->calculate(500_000, 3);
        $june = $this->calculator->calculate(500_000, 6);

        $this->assertSame(1_000, $march['srtb']);
        $this->assertSame(72_500, $march['its']);
        $this->assertSame(3_000, $june['srtb']);
        $this->assertSame(74_500, $june['its']);
    }

    public function test_amount_above_the_last_bracket_uses_thirty_percent(): void
    {
        $result = $this->calculator->calculate(600_000, 4);

        $this->assertSame(71_500 + 30_000, $result['its']);
    }

    public function test_salary_of_350000_matches_the_published_brackets(): void
    {
        $result = $this->calculator->calculate(350_000, 4);

        $this->assertSame(43_000, $result['its']);
    }
}
