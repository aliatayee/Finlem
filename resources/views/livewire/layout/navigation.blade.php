<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700" style="padding-top: env(safe-area-inset-top);">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center min-h-14 sm:min-h-16 py-2">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center w-[22vw] sm:w-[5vw]">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block w-full h-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.index')" wire:navigate>
                        {{ __('My Transactions') }}
                    </x-nav-link>
                    @if (auth()->user()->isAdmin())
                        <x-nav-link :href="route('admin.team')" :active="request()->routeIs('admin.team') || request()->routeIs('admin.member')" wire:navigate>
                            {{ __('Team') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.invitations')" :active="request()->routeIs('admin.invitations')" wire:navigate>
                            {{ __('Invitations') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')" wire:navigate>
                            {{ __('Settings') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Desktop-only actions -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                <x-theme-toggle />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Mobile-only: theme toggle (navigation itself lives in the bottom tab bar) -->
            <div class="flex items-center sm:hidden">
                <x-theme-toggle />
            </div>
        </div>
    </div>
</nav>
