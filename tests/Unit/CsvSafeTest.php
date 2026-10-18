<?php

namespace Tests\Unit;

use App\Support\CsvSafe;
use PHPUnit\Framework\TestCase;

class CsvSafeTest extends TestCase
{
    public function test_formula_prefixes_are_neutralised()
    {
        foreach (['=1+1', '+SUM(A1)', '-2+3', '@cmd'] as $value) {
            $this->assertSame("'" . $value, CsvSafe::cell($value));
        }
    }

    public function test_normal_values_are_untouched()
    {
        $this->assertSame('Lunch', CsvSafe::cell('Lunch'));
        $this->assertSame('', CsvSafe::cell(''));
        $this->assertSame(150.5, CsvSafe::cell(150.5));
        $this->assertNull(CsvSafe::cell(null));
    }
}