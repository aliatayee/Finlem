<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    // Create member form
    public string $createName = '';
    public string $createEmail = '';
    public string $createPassword = '';
    public string $createPassword_confirmation = '';
    public string $createRole = User::ROLE_MEMBER;

    // Edit member form
    public ?int $editUserId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editRole = User::ROLE_MEMBER;
    public bool $editIsActive = true;
    public string $editPassword = '';
    public string $editPassword_confirmation = '';

    #[On('open-create-member')]
    public function openCreate(): void
    {
        $this->reset(['createName', 'createEmail', 'createPassword', 'createPassword_confirmation']);
        $this->createRole = User::ROLE_MEMBER;
        $this->resetValidation();
        $this->dispatch('open-modal', 'create-member');
    }

    public function createMember(): void
    {
        $validated = $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'createPassword' => ['required', 'confirmed', Password::defaults()],
            'createRole' => ['required', 'in:admin,member'],
        ], [], [
            'createPassword' => 'password',
        ]);

        User::create([
            'name' => $validated['createName'],
            'email' => $validated['createEmail'],
            'password' => Hash::make($validated['createPassword']),
            'role' => $validated['createRole'],
            'is_active' => true,
            'invited_by' => Auth::id(),
            'email_verified_at' => now(),
        ]);

        $this->dispatch('close-modal', 'create-member');
        $this->dispatch('notify', message: "{$validated['createName']} was added to the team.");
    }

    public function openEdit(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editRole = $user->role;
        $this->editIsActive = $user->is_active;
        $this->editPassword = '';
        $this->editPassword_confirmation = '';
        $this->resetValidation();

        $this->dispatch('open-modal', 'edit-member');
    }

    public function updateMember(): void
    {
        $user = User::findOrFail($this->editUserId);
        $isSelf = $user->id === Auth::id();

        $rules = [
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        if (! $isSelf) {
            $rules['editRole'] = ['required', 'in:admin,member'];
        }

        // Only validate the password fields when the admin actually typed one —
        // leaving them blank means "keep the current password".
        if (filled($this->editPassword)) {
            $rules['editPassword'] = ['confirmed', Password::defaults()];
        }

        $validated = $this->validate($rules, [], ['editPassword' => 'password']);

        $data = [
            'name' => $validated['editName'],
            'email' => $validated['editEmail'],
        ];

        if (! $isSelf) {
            $data['role'] = $validated['editRole'];
            $data['is_active'] = $this->editIsActive;
        }

        if (filled($this->editPassword)) {
            $data['password'] = Hash::make($validated['editPassword']);
        }

        $user->update($data);

        $this->dispatch('close-modal', 'edit-member');
        $this->dispatch('notify', message: "{$user->name}'s account was updated.");
    }

    public function with(): array
    {
        $members = User::withSum(['transactions as collected_sum' => fn ($q) => $q->where('type', Transaction::TYPE_COLLECTION)], 'amount')
            ->withSum(['transactions as expenses_sum' => fn ($q) => $q->where('type', Transaction::TYPE_EXPENSE)], 'amount')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $user->collected_sum = (float) $user->collected_sum;
                $user->expenses_sum = (float) $user->expenses_sum;
                $user->balance_calc = $user->collected_sum - $user->expenses_sum;

                return $user;
            });

        return [
            'members' => $members,
            'orgCollected' => $members->sum('collected_sum'),
            'orgExpenses' => $members->sum('expenses_sum'),
            'orgBalance' => $members->sum('balance_calc'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Team') }}
            </h2>
            <button
                type="button"
                x-data=""
                x-on:click="$dispatch('open-create-member')"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                Add Member
            </button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Organization Balance" :amount="$orgBalance" colored icon="scale" />
                <x-stat-card label="Total Collected" :amount="$orgCollected" icon="inbound" />
                <x-stat-card label="Total Expenses" :amount="$orgExpenses" icon="outbound" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">Members</h3>
                </div>
                {{-- Mobile: card list --}}
                <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($members as $member)
                        <div wire:key="member-card-{{ $member->id }}" class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.member', $member) }}" wire:navigate class="font-medium text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $member->name }}</a>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $member->email }}</div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $member->isAdmin() ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                                        {{ ucfirst($member->role) }}
                                    </span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $member->is_active ? 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 py-2">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Collected</div>
                                    <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400"><x-money :amount="$member->collected_sum" /></div>
                                </div>
                                <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 py-2">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Expenses</div>
                                    <div class="text-sm font-medium text-rose-600 dark:text-rose-400"><x-money :amount="$member->expenses_sum" /></div>
                                </div>
                                <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 py-2">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Balance</div>
                                    <div class="text-sm font-medium"><x-money :amount="$member->balance_calc" colored /></div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button wire:click="openEdit({{ $member->id }})" class="flex-1 text-center text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-md py-1.5">Edit</button>
                                <a href="{{ route('admin.member', $member) }}" wire:navigate class="flex-1 text-center text-sm font-medium text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-md py-1.5">View</a>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-5 py-3">Member</th>
                                <th class="px-5 py-3">Role</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Collected</th>
                                <th class="px-5 py-3 text-right">Expenses</th>
                                <th class="px-5 py-3 text-right">Balance</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($members as $member)
                                <tr wire:key="member-{{ $member->id }}">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.member', $member) }}" wire:navigate class="font-medium text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $member->name }}</a>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $member->email }}</div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $member->isAdmin() ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                                            {{ ucfirst($member->role) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $member->is_active ? 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-emerald-600 dark:text-emerald-400"><x-money :amount="$member->collected_sum" /></td>
                                    <td class="px-5 py-3 text-right text-rose-600 dark:text-rose-400"><x-money :amount="$member->expenses_sum" /></td>
                                    <td class="px-5 py-3 text-right font-medium"><x-money :amount="$member->balance_calc" colored /></td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <button wire:click="openEdit({{ $member->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Edit</button>
                                        <a href="{{ route('admin.member', $member) }}" wire:navigate class="ms-3 text-sm text-gray-500 dark:text-gray-400 hover:underline">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Create member modal --}}
    <x-modal name="create-member" :show="$errors->has('createName') || $errors->has('createEmail') || $errors->has('createPassword') || $errors->has('createRole')" focusable maxWidth="md">
        <form wire:submit="createMember" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Add a team member</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Creates the account immediately with the password you set below — no invitation needed.</p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="createName" value="Full Name" />
                    <x-text-input wire:model="createName" id="createName" class="block mt-1 w-full" autofocus />
                    <x-input-error :messages="$errors->get('createName')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="createEmail" value="Email address" />
                    <x-text-input wire:model="createEmail" id="createEmail" type="email" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('createEmail')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="createPassword" value="Password" />
                        <x-text-input wire:model="createPassword" id="createPassword" type="password" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('createPassword')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="createPassword_confirmation" value="Confirm Password" />
                        <x-text-input wire:model="createPassword_confirmation" id="createPassword_confirmation" type="password" class="block mt-1 w-full" />
                    </div>
                </div>

                <div>
                    <x-input-label for="createRole" value="Role" />
                    <select wire:model="createRole" id="createRole" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full sm:w-48">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center">Cancel</x-secondary-button>
                <x-primary-button class="w-full sm:w-auto justify-center">Create Account</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Edit member modal --}}
    <x-modal name="edit-member" :show="$errors->has('editName') || $errors->has('editEmail') || $errors->has('editPassword')" focusable maxWidth="md">
        <form wire:submit="updateMember" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Edit member</h2>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="editName" value="Full Name" />
                    <x-text-input wire:model="editName" id="editName" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('editName')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="editEmail" value="Email address" />
                    <x-text-input wire:model="editEmail" id="editEmail" type="email" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('editEmail')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="editPassword" value="New Password" />
                        <x-text-input wire:model="editPassword" id="editPassword" type="password" class="block mt-1 w-full" placeholder="Leave blank to keep current" />
                        <x-input-error :messages="$errors->get('editPassword')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="editPassword_confirmation" value="Confirm New Password" />
                        <x-text-input wire:model="editPassword_confirmation" id="editPassword_confirmation" type="password" class="block mt-1 w-full" />
                    </div>
                </div>

                @if ($editUserId !== auth()->id())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="editRole" value="Role" />
                            <select wire:model="editRole" id="editRole" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="editIsActive" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 focus:ring-indigo-500">
                                Active account
                            </label>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500">You can't change your own role or active status here — ask another admin.</p>
                @endif
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center">Cancel</x-secondary-button>
                <x-primary-button class="w-full sm:w-auto justify-center">Save Changes</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
