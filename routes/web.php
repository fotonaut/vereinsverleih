<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanRequestController;
use App\Http\Controllers\Manage;
use App\Http\Controllers\PageController;
use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('impressum', [PageController::class, 'imprint'])->name('imprint');
Route::get('datenschutz', [PageController::class, 'privacy'])->name('privacy');

Route::get('katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('katalog/{item}', [CatalogController::class, 'show'])->name('catalog.show');
Route::post('katalog/{item}/anfragen', [LoanRequestController::class, 'store'])
    ->middleware('throttle:6,1')->name('requests.store');

Route::post('katalog/{item}/warteliste', [WaitlistController::class, 'store'])
    ->middleware('throttle:6,1')->name('waitlist.store');
Route::get('warteliste/{token}', [WaitlistController::class, 'show'])->name('waitlist.show');
Route::get('warteliste/{token}/bestaetigen', [WaitlistController::class, 'verify'])->name('waitlist.verify');
Route::post('warteliste/{token}/abmelden', [WaitlistController::class, 'cancel'])->name('waitlist.cancel');

Route::get('anfragen/{token}', [LoanRequestController::class, 'show'])->name('requests.show');
Route::get('anfragen/{token}/bestaetigen', [LoanRequestController::class, 'verify'])->name('requests.verify');
Route::post('anfragen/{token}/verlaengern', [LoanRequestController::class, 'extend'])
    ->middleware('throttle:6,1')->name('requests.extend');
Route::post('anfragen/{token}/serie-stornieren', [LoanRequestController::class, 'cancelSeries'])->name('requests.cancel-series');
Route::post('anfragen/{token}/stornieren', [LoanRequestController::class, 'cancel'])->name('requests.cancel');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('verwaltung')->name('manage.')->group(function () {
        Route::get('gegenstaende/{item}/kalender', Manage\ItemCalendarController::class)->name('items.calendar');
        Route::resource('gegenstaende', Manage\ItemController::class)
            ->parameters(['gegenstaende' => 'item'])->except('show')->names('items');
        Route::get('eingang', [Manage\IncomingController::class, 'index'])->name('incoming.index');
        Route::patch('eingang/{loanRequest}', [Manage\IncomingController::class, 'update'])->name('incoming.update');
        Route::patch('eingang/{loanRequest}/verlaengerung/{extension}', [Manage\IncomingController::class, 'decideExtension'])->name('incoming.extension');
        Route::patch('eingang/serie/{series}', [Manage\IncomingController::class, 'decideSeries'])->name('incoming.series');
        Route::get('warteliste', Manage\WaitlistOverviewController::class)->name('waitlist.index');
        Route::get('ausgang', [Manage\OutgoingController::class, 'index'])->name('outgoing.index');
        Route::get('export/ausleihen.csv', [Manage\ExportController::class, 'loans'])->name('export.loans');
        Route::get('verein', [Manage\ClubController::class, 'edit'])->name('club.edit');
        Route::put('verein', [Manage\ClubController::class, 'update'])->name('club.update');
        Route::get('benachrichtigungen', [Manage\NotificationSettingsController::class, 'edit'])->name('notifications.edit');
        Route::put('benachrichtigungen', [Manage\NotificationSettingsController::class, 'update'])->name('notifications.update');
        Route::post('benachrichtigungen/test', [Manage\NotificationSettingsController::class, 'test'])
            ->middleware('throttle:5,1')->name('notifications.test');
        Route::get('mitglieder', [Manage\MemberController::class, 'index'])->name('members.index');
        Route::post('mitglieder', [Manage\MemberController::class, 'store'])->name('members.store');
        Route::delete('mitglieder/{member}', [Manage\MemberController::class, 'destroy'])->name('members.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
