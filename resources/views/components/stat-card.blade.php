@props(['label', 'amount', 'colored' => false, 'icon' => 'wallet'])

@php
    $icons = [
        'wallet' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75v10.5A2.25 2.25 0 005.25 19.5z',
        'inbound' => 'M9 6.75V15m0 0l-3-3m3 3l3-3M3.75 3v18M3.75 3H8.25a3 3 0 013 3v0',
        'outbound' => 'M15 17.25V9m0 0l3 3m-3-3l-3 3M20.25 21V3M20.25 21h-4.5a3 3 0 01-3-3v0',
        'scale' => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z',
    ];
@endphp

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
        <span class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] ?? $icons['wallet'] }}" />
            </svg>
        </span>
    </div>
    <p class="mt-2 text-2xl font-semibold">
        <x-money :amount="$amount" :colored="$colored" />
    </p>
</div>
