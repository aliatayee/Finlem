<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'from', history: true)]
    public string $fromDate = '';

    #[Url(as: 'to', history: true)]
    public string $toDate = '';

    public function updated($property): void
    {
        if (in_array($property, ['fromDate', 'toDate'])) {
            $this->resetPage('collectionsPage');
            $this->resetPage('expensesPage');
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['fromDate', 'toDate']);
        $this->resetPage('collectionsPage');
        $this->resetPage('expensesPage');
    }

    #[On('transaction-saved')]
    public function refresh(): void
    {
        //
    }

    public function edit(int $transactionId): void
    {
        $transaction = Auth::user()->transactions()->findOrFail($transactionId);
        $this->dispatch('open-transaction-form', type: $transaction->type, transactionId: $transaction->id);
    }

    public function delete(int $transactionId): void
    {
        Auth::user()->transactions()->findOrFail($transactionId)->delete();
        $this->dispatch('notify', message: 'Transaction deleted.');
    }

    protected function baseQuery()
    {
        $query = Auth::user()->transactions()->latest('occurred_on')->latest('id');

        if ($this->fromDate) {
            $query->whereDate('occurred_on', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate('occurred_on', '<=', $this->toDate);
        }

        return $query;
    }

    public function with(): array
    {
        return [
            'collectionTransactions' => (clone $this->baseQuery())->collections()->paginate(10, pageName: 'collectionsPage'),
            'expenseTransactions' => (clone $this->baseQuery())->expenses()->paginate(10, pageName: 'expensesPage'),
            'filteredCollected' => (clone $this->baseQuery())->collections()->sum('amount'),
            'filteredExpenses' => (clone $this->baseQuery())->expenses()->sum('amount'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('My Transactions') }}
            </h2>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <button
                    x-data=""
                    x-on:click="$dispatch('open-transaction-form', { type: 'collection' })"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition"
                >
                    Collection
                </button>
                <button
                    x-data=""
                    x-on:click="$dispatch('open-transaction-form', { type: 'expense' })"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-rose-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition"
                >
                    Expense
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-stat-card label="Collected (filtered)" :amount="$filteredCollected" icon="inbound" />
                <x-stat-card label="Expenses (filtered)" :amount="$filteredExpenses" icon="outbound" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                        <input type="date" wire:model.live="fromDate" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                        <input type="date" wire:model.live="toDate" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    @if ($fromDate || $toDate)
                        <button wire:click="resetFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline text-left">Clear filters</button>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <x-transaction-table title="Cash Collections" :transactions="$collectionTransactions" type="collection" empty-message="No collections match these filters.">
                    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $collectionTransactions->links() }}
                    </div>
                </x-transaction-table>

                <x-transaction-table title="Cash Expenses" :transactions="$expenseTransactions" type="expense" empty-message="No expenses match these filters.">
                    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $expenseTransactions->links() }}
                    </div>
                </x-transaction-table>
            </div>
        </div>
    </div>

    <livewire:transactions.form-modal />
</div>
