@props([
    'title',
    'transactions',
    'type',
    'editable' => true,
    'emptyMessage' => 'Nothing recorded yet.',
])

@php
    $amountColor = $type === 'collection' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
    $sign = $type === 'collection' ? '+' : '−';
    $dotColor = $type === 'collection' ? 'bg-emerald-500' : 'bg-rose-500';
@endphp

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
        <span class="h-2 w-2 rounded-full {{ $dotColor }}"></span>
        <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ $title }}</h3>
    </div>

    @if ($transactions->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $emptyMessage }}
        </div>
    @else
        {{-- Mobile: card list --}}
        <div class="sm:hidden max-h-[420px] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($transactions as $transaction)
                <div wire:key="{{ $type }}-card-{{ $transaction->id }}" class="px-4 py-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $transaction->category ?: 'Uncategorized' }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $transaction->occurred_on->format('M j, Y') }}</span>
                        </div>
                        @if ($transaction->description)
                            <p
                                x-data="{ expanded: false }"
                                x-on:click="expanded = !expanded"
                                :class="expanded ? '' : 'truncate'"
                                class="text-sm text-gray-500 dark:text-gray-400 cursor-pointer"
                            >{{ $transaction->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="font-semibold {{ $amountColor }}">{{ $sign }}<x-money :amount="$transaction->amount" /></span>
                        @if ($editable)
                            <div class="flex items-center gap-1">
                                <button wire:click="edit({{ $transaction->id }})" class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                                </button>
                                <button wire:click="delete({{ $transaction->id }})" wire:confirm="Delete this transaction?" class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden sm:block max-h-[420px] overflow-y-auto overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Notes</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        @if ($editable)
                            <th class="px-5 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($transactions as $transaction)
                        <tr wire:key="{{ $type }}-{{ $transaction->id }}">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $transaction->occurred_on->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $transaction->category ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $transaction->description ?: '—' }}</td>
                            <td class="px-5 py-3 text-right font-medium {{ $amountColor }}">
                                {{ $sign }}<x-money :amount="$transaction->amount" />
                            </td>
                            @if ($editable)
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <button wire:click="edit({{ $transaction->id }})" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                                    </button>
                                    <button wire:click="delete({{ $transaction->id }})" wire:confirm="Delete this transaction?" class="ms-2 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $slot }}
    @endif
</div>
