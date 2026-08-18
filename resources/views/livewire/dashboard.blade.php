<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[On('transaction-saved')]
    public function refresh(): void
    {
        // Re-render to reflect the new totals and recent activity.
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'totalCollected' => $user->totalCollected(),
            'totalExpenses' => $user->totalExpenses(),
            'balance' => $user->balance(),
            'recentCollections' => $user->transactions()->collections()->latest('occurred_on')->latest('id')->take(5)->get(),
            'recentExpenses' => $user->transactions()->expenses()->latest('occurred_on')->latest('id')->take(5)->get(),
        ];
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
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <button
                    x-data=""
                    x-on:click="$dispatch('open-transaction-form', { type: 'collection' })"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                    Collection
                </button>
                <button
                    x-data=""
                    x-on:click="$dispatch('open-transaction-form', { type: 'expense' })"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-rose-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                    Expense
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="My Balance" :amount="$balance" colored icon="scale" />
                <x-stat-card label="Total Collected" :amount="$totalCollected" icon="inbound" />
                <x-stat-card label="Total Expenses" :amount="$totalExpenses" icon="outbound" />
            </div>

            <div class="space-y-6">
                <x-transaction-table title="Recent Collections" :transactions="$recentCollections" type="collection" empty-message="No collections recorded yet.">
                    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('transactions.index') }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                    </div>
                </x-transaction-table>

                <x-transaction-table title="Recent Expenses" :transactions="$recentExpenses" type="expense" empty-message="No expenses recorded yet.">
                    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('transactions.index') }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                    </div>
                </x-transaction-table>
            </div>
        </div>
    </div>

    <livewire:transactions.form-modal />
</div>
