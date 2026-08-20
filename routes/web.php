<?php

use App\Http\Controllers\TransactionPdfController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'dashboard')->name('dashboard');

    Volt::route('transactions', 'transactions.index')->name('transactions.index');
    Route::get('transactions/export/pdf', TransactionPdfController::class)->name('transactions.export-pdf');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Volt::route('team', 'admin.team-report')->name('team');
        Volt::route('members/{user}', 'admin.member-detail')->name('member');
        Volt::route('invitations', 'admin.invitations')->name('invitations');
        Volt::route('settings', 'admin.settings')->name('settings');
    });
});

require __DIR__.'/auth.php';
