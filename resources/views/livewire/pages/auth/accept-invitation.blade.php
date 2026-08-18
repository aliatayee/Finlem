<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $token = '';
    public string $name = '';
    public string $password = '';
    public string $password_confirmation = '';

    public ?Invitation $invitation = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->invitation = Invitation::where('token', $token)->first();
    }

    public function accept(): void
    {
        abort_unless($this->invitation && ! $this->invitation->isAccepted() && ! $this->invitation->isExpired(), 404);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $this->invitation->email,
            'password' => Hash::make($validated['password']),
            'role' => $this->invitation->role,
            'is_active' => true,
            'invited_by' => $this->invitation->invited_by,
            'email_verified_at' => now(),
        ]);

        $this->invitation->update(['accepted_at' => now()]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if (! $invitation)
        <div class="text-center">
            <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Invitation not found</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">This invitation link is invalid. Ask your admin to send a new one.</p>
            <a href="{{ route('login') }}" wire:navigate class="mt-4 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Back to login</a>
        </div>
    @elseif ($invitation->isAccepted())
        <div class="text-center">
            <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Already accepted</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">This invitation has already been used. Please log in instead.</p>
            <a href="{{ route('login') }}" wire:navigate class="mt-4 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Back to login</a>
        </div>
    @elseif ($invitation->isExpired())
        <div class="text-center">
            <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Invitation expired</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">This invitation has expired. Ask your admin to resend it.</p>
            <a href="{{ route('login') }}" wire:navigate class="mt-4 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Back to login</a>
        </div>
    @else
        <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">Join the petty cash team</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            You've been invited as a <strong>{{ ucfirst($invitation->role) }}</strong>. Set your name and password to get started.
        </p>

        <form wire:submit="accept">
            <div>
                <x-input-label for="invitation_email" value="Email" />
                <x-text-input id="invitation_email" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700" type="email" value="{{ $invitation->email }}" disabled />
            </div>

            <div class="mt-4">
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button>
                    {{ __('Create Account') }}
                </x-primary-button>
            </div>
        </form>
    @endif
</div>
