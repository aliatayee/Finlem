<?php

namespace App\Livewire\Transactions;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class FormModal extends Component
{
    public ?int $transactionId = null;

    public string $type = Transaction::TYPE_COLLECTION;

    public string $amount = '';

    public string $category = '';

    public string $description = '';

    public string $occurred_on = '';

    public function mount(): void
    {
        $this->occurred_on = now()->toDateString();
    }

    #[On('open-transaction-form')]
    public function openForm(string $type, ?int $transactionId = null): void
    {
        $this->resetValidation();
        $this->type = $type;
        $this->transactionId = $transactionId;
        $this->category = '';
        $this->description = '';
        $this->amount = '';
        $this->occurred_on = now()->toDateString();

        if ($transactionId) {
            $transaction = Auth::user()->transactions()->findOrFail($transactionId);
            $this->type = $transaction->type;
            $this->amount = (string) $transaction->amount;
            $this->category = (string) $transaction->category;
            $this->description = (string) $transaction->description;
            $this->occurred_on = $transaction->occurred_on->toDateString();
        }

        $this->dispatch('open-modal', 'transaction-form');
    }

    public function categoryOptions(): array
    {
        return Category::ofType($this->type)->orderBy('name')->pluck('name')->all();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type' => ['required', 'in:collection,expense'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
        ]);

        if ($this->transactionId) {
            $transaction = Auth::user()->transactions()->findOrFail($this->transactionId);
            $transaction->update($validated);
        } else {
            Auth::user()->transactions()->create($validated);
        }

        $message = $this->transactionId ? 'Transaction updated.' : 'Transaction recorded.';

        $this->dispatch('close-modal', 'transaction-form');
        $this->dispatch('transaction-saved');
        $this->dispatch('notify', message: $message);

        $this->transactionId = null;
    }
}
