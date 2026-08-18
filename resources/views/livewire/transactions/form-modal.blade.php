<div>
    <x-modal name="transaction-form" :show="$errors->isNotEmpty()" focusable maxWidth="md">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $transactionId ? 'Edit' : 'Record' }} {{ $type === 'collection' ? 'Cash Collection' : 'Cash Expense' }}
            </h2>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="amount" :value="__('Amount')" />
                    <div class="relative mt-1">
                        @php $symbol = \App\Support\Currency::symbol(); @endphp
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-gray-500 text-sm">
                            {{ $symbol }}
                        </span>
                        <x-text-input wire:model="amount" id="amount" type="number" step="0.01" min="0.01" class="block w-full {{ strlen($symbol) > 1 ? 'pl-12' : 'pl-7' }}" placeholder="0.00" autofocus />
                    </div>
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="category" :value="__('Category')" />
                    <select wire:model="category" id="category" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                        <option value="">No category</option>
                        @foreach ($this->categoryOptions() as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="occurred_on" :value="__('Date')" />
                    <x-text-input wire:model="occurred_on" id="occurred_on" type="date" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('occurred_on')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="description" :value="__('Notes (optional)')" />
                    <textarea wire:model="description" id="description" rows="2" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full" placeholder="What was this for?"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="w-full sm:w-auto justify-center {{ $type === 'collection' ? 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 focus:ring-emerald-500' : 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 focus:ring-rose-500' }}">
                    {{ $transactionId ? __('Save Changes') : __('Add ' . ($type === 'collection' ? 'Collection' : 'Expense')) }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
