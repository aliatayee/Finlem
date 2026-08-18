<?php

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $email = '';
    public string $role = User::ROLE_MEMBER;

    public function send(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,member'],
        ]);

        $existing = Invitation::where('email', $validated['email'])->pending()->first();
        if ($existing) {
            $this->addError('email', 'An active invitation already exists for this email.');
            return;
        }

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invited_by' => Auth::id(),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        $this->reset(['email', 'role']);
        $this->dispatch('notify', message: 'Invitation sent to ' . $invitation->email . '.');
    }

    public function resend(int $invitationId): void
    {
        $invitation = Invitation::findOrFail($invitationId);
        $invitation->update(['expires_at' => now()->addDays(7)]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        $this->dispatch('notify', message: 'Invitation resent to ' . $invitation->email . '.');
    }

    public function revoke(int $invitationId): void
    {
        Invitation::findOrFail($invitationId)->delete();
        $this->dispatch('notify', message: 'Invitation revoked.');
    }

    public function with(): array
    {
        return [
            'pending' => Invitation::pending()->latest()->get(),
            'expired' => Invitation::expired()->latest()->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Team Invitations') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Invite a new member</h3>
                <form wire:submit="send" class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="w-full sm:flex-1 sm:min-w-[220px]">
                        <x-input-label for="email" value="Email address" />
                        <x-text-input wire:model="email" id="email" type="email" class="block mt-1 w-full" placeholder="teammate@example.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div class="w-full sm:w-auto">
                        <x-input-label for="role" value="Role" />
                        <select wire:model="role" id="role" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 mt-1 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <x-primary-button class="w-full sm:w-auto justify-center">Send Invitation</x-primary-button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">Pending Invitations</h3>
                </div>
                @if ($pending->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No pending invitations.</div>
                @else
                    {{-- Mobile: card list --}}
                    <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($pending as $invitation)
                            <div wire:key="inv-card-{{ $invitation->id }}" class="p-4 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="text-gray-800 dark:text-gray-200 truncate">{{ $invitation->email }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($invitation->role) }} · invited by {{ $invitation->inviter->name }} · expires {{ $invitation->expires_at->format('M j, Y') }}</div>
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <button
                                        type="button"
                                        x-data=""
                                        x-on:click="
                                            navigator.clipboard.writeText('{{ route('invitations.accept', $invitation->token) }}');
                                            $dispatch('notify', { message: 'Invite link copied.' });
                                        "
                                        class="flex-1 text-center text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-md py-1.5"
                                    >Copy link</button>
                                    <button wire:click="resend({{ $invitation->id }})" class="flex-1 text-center text-sm font-medium text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-md py-1.5">Resend</button>
                                    <button wire:click="revoke({{ $invitation->id }})" wire:confirm="Revoke this invitation?" class="flex-1 text-center text-sm font-medium text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 rounded-md py-1.5">Revoke</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop: table --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="px-5 py-3">Email</th>
                                    <th class="px-5 py-3">Role</th>
                                    <th class="px-5 py-3">Invited by</th>
                                    <th class="px-5 py-3">Expires</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($pending as $invitation)
                                    <tr wire:key="inv-{{ $invitation->id }}">
                                        <td class="px-5 py-3 text-gray-800 dark:text-gray-200">{{ $invitation->email }}</td>
                                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($invitation->role) }}</td>
                                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $invitation->inviter->name }}</td>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $invitation->expires_at->format('M j, Y') }}</td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                x-data=""
                                                x-on:click="
                                                    navigator.clipboard.writeText('{{ route('invitations.accept', $invitation->token) }}');
                                                    $dispatch('notify', { message: 'Invite link copied.' });
                                                "
                                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                            >Copy link</button>
                                            <button wire:click="resend({{ $invitation->id }})" class="ms-3 text-sm text-gray-500 dark:text-gray-400 hover:underline">Resend</button>
                                            <button wire:click="revoke({{ $invitation->id }})" wire:confirm="Revoke this invitation?" class="ms-3 text-sm text-rose-600 dark:text-rose-400 hover:underline">Revoke</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($expired->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Expired Invitations</h3>
                    </div>
                    {{-- Mobile: card list --}}
                    <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($expired as $invitation)
                            <div wire:key="exp-card-{{ $invitation->id }}" class="p-4 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-gray-600 dark:text-gray-300 truncate">{{ $invitation->email }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">Expired {{ $invitation->expires_at->diffForHumans() }}</div>
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    <button wire:click="resend({{ $invitation->id }})" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Resend</button>
                                    <button wire:click="revoke({{ $invitation->id }})" class="text-sm font-medium text-rose-600 dark:text-rose-400">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop: table --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($expired as $invitation)
                                    <tr wire:key="exp-{{ $invitation->id }}">
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $invitation->email }}</td>
                                        <td class="px-5 py-3 text-gray-400 dark:text-gray-500">Expired {{ $invitation->expires_at->diffForHumans() }}</td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            <button wire:click="resend({{ $invitation->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Resend</button>
                                            <button wire:click="revoke({{ $invitation->id }})" class="ms-3 text-sm text-rose-600 dark:text-rose-400 hover:underline">Remove</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
