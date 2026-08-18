<?php

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $newCollectionCategory = '';
    public string $newExpenseCategory = '';

    public function addCategory(string $type): void
    {
        $property = $type === Transaction::TYPE_COLLECTION ? 'newCollectionCategory' : 'newExpenseCategory';

        $validated = $this->validate([
            $property => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('type', $type),
            ],
        ]);

        Category::create([
            'type' => $type,
            'name' => trim($validated[$property]),
        ]);

        $this->reset($property);
        $this->dispatch('notify', message: 'Category added.');
    }

    public function deleteCategory(int $categoryId): void
    {
        Category::findOrFail($categoryId)->delete();
        $this->dispatch('notify', message: 'Category removed.');
    }

    public function with(): array
    {
        return [
            'collectionCategories' => Category::ofType(Transaction::TYPE_COLLECTION)->orderBy('name')->get(),
            'expenseCategories' => Category::ofType(Transaction::TYPE_EXPENSE)->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Currency</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Currently set to <span class="font-medium text-gray-700 dark:text-gray-300">{{ config('app.currency') }}</span>.
                    Change <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">APP_CURRENCY</code> in your <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">.env</code> file to update it.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Collection Categories</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <form wire:submit="addCategory('collection')" class="flex gap-2">
                            <x-text-input wire:model="newCollectionCategory" placeholder="e.g. Grants" class="block w-full" />
                            <x-primary-button class="shrink-0 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 focus:ring-emerald-500">Add</x-primary-button>
                        </form>
                        <x-input-error :messages="$errors->get('newCollectionCategory')" />

                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($collectionCategories as $category)
                                <li wire:key="cc-{{ $category->id }}" class="flex items-center justify-between py-2 text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                                    <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Remove this category?" class="text-gray-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                    </button>
                                </li>
                            @empty
                                <li class="py-4 text-sm text-gray-400 dark:text-gray-500 text-center">No collection categories yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Expense Categories</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <form wire:submit="addCategory('expense')" class="flex gap-2">
                            <x-text-input wire:model="newExpenseCategory" placeholder="e.g. Software" class="block w-full" />
                            <x-primary-button class="shrink-0 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 focus:ring-rose-500">Add</x-primary-button>
                        </form>
                        <x-input-error :messages="$errors->get('newExpenseCategory')" />

                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($expenseCategories as $category)
                                <li wire:key="ec-{{ $category->id }}" class="flex items-center justify-between py-2 text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                                    <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Remove this category?" class="text-gray-400 hover:text-rose-600 dark:hover:text-rose-400" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                    </button>
                                </li>
                            @empty
                                <li class="py-4 text-sm text-gray-400 dark:text-gray-500 text-center">No expense categories yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
