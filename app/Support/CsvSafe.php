<?php

namespace App\Support;

class CsvSafe
{
    // Student-typed text starting with = + - @ can run as a formula when an admin opens the export.
    public static function cell($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}