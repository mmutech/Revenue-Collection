<?php

use App\Http\Controllers\LodgementDocumentController;
use App\Http\Controllers\PaymentReceiptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware('guest')->group(function () {
    Route::livewire('/', 'pages::auth.login')->name('login');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::livewire('/dashboard', 'pages::dashboard.index')->name('dashboard');
    Route::livewire('/profile', 'pages::profile')->name('profile');
    Route::livewire('/notifications', 'pages::notifications.index')->name('notifications.index');

    Route::livewire('/payments', 'pages::payments.index')->name('payments.index');
    Route::livewire('/payments/create', 'pages::payments.create')->name('payments.create');
    Route::get('/payments/{payment}/receipt', PaymentReceiptController::class)->name('payments.receipt');
    Route::livewire('/payments/{payment}', 'pages::payments.show')->name('payments.show');

    Route::livewire('/shifts', 'pages::shifts.index')->name('shifts.index');

    Route::livewire('/collections', 'pages::collections.index')->name('collections.index');
    Route::livewire('/collections/queue', 'pages::collections.queue')->name('collections.queue');
    Route::livewire('/collections/{batch}', 'pages::collections.show')->name('collections.show');

    Route::livewire('/lodgements', 'pages::lodgements.index')->name('lodgements.index');
    Route::livewire('/lodgements/create', 'pages::lodgements.create')->name('lodgements.create');
    Route::get('/documents/lodgements/{document}', LodgementDocumentController::class)->name('documents.lodgement');
    Route::livewire('/lodgements/{lodgement}', 'pages::lodgements.show')->name('lodgements.show');

    Route::livewire('/treasury', 'pages::treasury.index')->name('treasury.index');
    Route::livewire('/treasury/{lodgement}', 'pages::treasury.show')->name('treasury.show');

    Route::livewire('/reconciliation', 'pages::reconciliation.index')->name('reconciliation.index');
    Route::livewire('/reconciliation/{run}', 'pages::reconciliation.show')->name('reconciliation.show');

    Route::livewire('/reports', 'pages::reports.index')->name('reports.index');
    Route::livewire('/reports/exports', 'pages::reports.exports')->name('reports.exports');

    Route::livewire('/discrepancies', 'pages::discrepancies.index')->name('discrepancies.index');
    Route::livewire('/discrepancies/{discrepancy}', 'pages::discrepancies.show')->name('discrepancies.show');

    Route::livewire('/treatment', 'pages::treatment.index')->name('treatment.index');
    Route::livewire('/treatment/create', 'pages::treatment.create')->name('treatment.create');
    Route::livewire('/treatment/{treatment}', 'pages::treatment.show')->name('treatment.show');

    Route::livewire('/admin/users', 'pages::admin.index')->name('admin.users');
    Route::livewire('/admin/branches', 'pages::admin.index')->name('admin.branches');
    Route::livewire('/admin/reference', 'pages::admin.index')->name('admin.reference');
    Route::livewire('/admin/workflows', 'pages::admin.index')->name('admin.workflows');
    Route::livewire('/admin/roles', 'pages::admin.index')->name('admin.roles');
    Route::livewire('/admin/settings', 'pages::admin.index')->name('admin.settings');

    Route::livewire('/audit', 'pages::audit.index')->name('audit.index');
});
