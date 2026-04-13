<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Language switcher
Route::get('/lang/{locale}', function (string $locale) {
    $supported = ['en', 'es', 'pt', 'fr'];
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
    }
    return redirect()->back()->withInput();
})->name('lang.switch');

// Dashboard — requires auth
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Listings — public read
Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');

// /listings/create must be defined BEFORE /listings/{listing} to avoid the wildcard swallowing it
Route::get('/listings/create', [ListingController::class, 'create'])->middleware('auth')->name('listings.create');

Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');

// Listings — auth required for write
Route::middleware('auth')->group(function () {
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::patch('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    // Reviews
    Route::post('/listings/{listing}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // Media
    Route::post('/listings/{listing}/media', [MediaController::class, 'store'])->name('media.store');

    // Admin: approve review
    Route::post('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');

    // Inquiries
    Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::patch('/inquiries/{inquiry}/status', [InquiryController::class, 'updateStatus'])->name('inquiries.updateStatus');

    // Reservations
    Route::get('/my-reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/{listing}', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
    Route::post('/reservations/{reservation}/complete', [ReservationController::class, 'complete'])->name('reservations.complete');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
});

require __DIR__.'/auth.php';
