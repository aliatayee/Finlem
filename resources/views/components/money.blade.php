@props(['amount', 'colored' => false])

@php
    $value = (float) $amount;
    $class = '';

    if ($colored) {
        $class = $value > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($value < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400');
    }
@endphp

<span {{ $attributes->class([$class]) }}>{{ \Illuminate\Support\Number::currency($value, in: config('app.currency')) }}</span>
