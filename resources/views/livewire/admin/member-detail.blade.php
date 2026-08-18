<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function with(): array
    {
        return [
            'collectionTransactions' => $this->user->transactions()->collections()->latest('occurred_on')->latest('id')->paginate(10, pageName: 'collectionsPage'),
            'expenseTransactions' => $this->user->transactions()->expenses()->latest('occurred_on')->latest('id')->paginate(10, pageName: 'expensesPage'),
            'totalCollected' => $this->user->totalCollected(),
            'totalExpenses' => $this->user->totalExpenses(),
            'balance' => $this->user->balance(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                    {{ $user->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
            </div>
            <a href="{{ route('admin.team') }}" wire:navigate class="shrink-0 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Balance" :amount="$balance" colored icon="scale" />
                <x-stat-card label="Total Collected" :amount="$totalCollected" icon="inbound" />
                <x-stat-card label="Total Expenses" :amount="$totalExpenses" icon="outbound" />
            </div>

            <div class="space-y-6">
                <x-transaction-table title="Collections" :transactions="$collectionTransactions" type="collection" :editable="false" empty-message="No collections recorded yet.">
                    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $collectionTransactions->links() }}
                    </div>
                </x-transaction-table>

                <x-transaction-table title="Expenses" :transactions="$expenseTransactions" type="expense" :editable="false" empty-message="No expenses recorded yet.">
                    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $expenseTransactions->links() }}
                    </div>
                </x-transaction-table>
            </div>
        </div>
    </div>
</div>
