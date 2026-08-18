<?php

namespace App\Support;

class Currency
{
    /**
     * A short display symbol/code for the configured currency.
     */
    public static function symbol(): string
    {
        return match (config('app.currency')) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'PKR' => '₨',
            'INR' => '₹',
            default => config('app.currency'),
        };
    }
}
