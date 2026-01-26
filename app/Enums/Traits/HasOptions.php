<?php

namespace App\Enums\Traits;

trait HasOptions
{
    /**
     * get enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
