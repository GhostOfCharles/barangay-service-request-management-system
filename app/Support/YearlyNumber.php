<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Generates numbers like SR-2026-0006 or TK-2026-0012 that restart at 0001 every year.
 * Call inside a database transaction so two saves cannot take the same number.
 */
class YearlyNumber
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function next(string $modelClass, string $column, string $prefix): string
    {
        $yearPrefix = $prefix.'-'.now()->year.'-';

        $last = $modelClass::query()
            ->where($column, 'like', $yearPrefix.'%')
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);

        $nextNumber = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $yearPrefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
