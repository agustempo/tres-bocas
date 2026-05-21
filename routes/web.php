<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserPreferencesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MareaController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Movilidad\AlertasServicioController;
use App\Http\Controllers\Movilidad\AvistajesController;
use App\Http\Controllers\Movilidad\MuellesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Admin\Movilidad\MuellesController as AdminMuellesController;
use App\Http\Controllers\Admin\Movilidad\PatronesController as AdminPatronesController;
use App\Http\Controllers\Admin\ServicioSuspensionController;
use App\Http\Controllers\HorariosController;
use App\Http\Controllers\MuellesComunidadController;
use App\Http\Controllers\PatronesComunidadController;
use Illuminate\Support\Facades\Route;

// Root → dashboard (requires auth; guests redirected to login)
Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home');

// /dashboard backward-compat redirect
Route::redirect('/dashboard', '/')->name('dashboard');

// Services / listings discovery page (was /)
Route::get('/servicios', HomeController::class)->name('servicios.index');

// Live search endpoint — public
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

// Useful local information
Route::get('/marea', [MareaController::class, 'index'])->name('marea.index');
Route::redirect('/condiciones', '/marea')->name('condiciones');

// Movilidad — public read
Route::prefix('movilidad')->name('movilidad.')->group(function () {
    Route::get('/', [MuellesController::class, 'index'])->name('index');
    Route::get('/muelles/{slug}', [MuellesController::class, 'show'])->name('muelles.show');
});

// Movilidad — auth required (reportar)
Route::prefix('movilidad')->name('movilidad.')->middleware('auth')->group(function () {
    Route::post('/avistajes', [AvistajesController::class, 'store'])->name('avistajes.store');
    Route::post('/avistajes/{id}/confirmar', [AvistajesController::class, 'confirmar'])->name('avistajes.confirmar');
    Route::post('/alertas', [AlertasServicioController::class, 'store'])->name('alertas.store');
    Route::delete('/alertas/{alerta}', [AlertasServicioController::class, 'destroy'])->name('alertas.destroy');
});

// Horarios — public per-muelle view
Route::get('/horarios/{muelle:slug}', [HorariosController::class, 'byMuelle'])->name('horarios.muelle');

// Horarios — personal schedule screen (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/horarios', [HorariosController::class, 'index'])->name('horarios.index');
    Route::post('/horarios/salidas/{patron}/confirmar', [HorariosController::class, 'confirmarSalida'])->name('horarios.salidas.confirmar');
    Route::post('/horarios/comunidad', [PatronesComunidadController::class, 'store'])->name('horarios.comunidad.store');
    Route::post('/muelles/comunidad', [MuellesComunidadController::class, 'store'])->name('muelles.comunidad.store');
});
Route::post('/horarios/salidas/{patron}/reaccionar', [HorariosController::class, 'reaccionar'])->name('horarios.salidas.reaccionar');
Route::post('/horarios/comunidad/{patron}/reaccionar', [PatronesComunidadController::class, 'reaccionar'])->name('horarios.comunidad.reaccionar');

// Language switcher
Route::get('/lang/{locale}', function (string $locale) {
    $supported = ['en', 'es', 'pt', 'fr'];
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
    }
    return redirect()->back()->withInput();
})->name('lang.switch');

// (dashboard moved to / above)

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User preferences
    Route::patch('/user/muelle', [UserPreferencesController::class, 'updateMuelle'])->name('user.muelle.update');
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

// ── Admin — Movilidad ────────────────────────────────────────
Route::prefix('admin/movilidad')->name('admin.movilidad.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/',                              [AdminMuellesController::class, 'index'])->name('index');
        Route::get('/muelles/{muelle}',              [AdminMuellesController::class, 'editor'])->name('muelles.editor');
        Route::get('/muelles/{muelle}/preview',      [AdminMuellesController::class, 'preview'])->name('muelles.preview');
        Route::post('/patrones',                     [AdminPatronesController::class, 'store'])->name('patrones.store');
        Route::patch('/patrones/{patron}',           [AdminPatronesController::class, 'update'])->name('patrones.update');
        Route::delete('/patrones/{patron}',          [AdminPatronesController::class, 'destroy'])->name('patrones.destroy');
        Route::post('/patrones/{patron}/validar',    [AdminPatronesController::class, 'validar'])->name('patrones.validar');
        Route::post('/patrones/validar-bulk',        [AdminPatronesController::class, 'validarBulk'])->name('patrones.validar-bulk');
        Route::get('/patrones/{patron}/avistajes',   [AdminPatronesController::class, 'avistajes'])->name('patrones.avistajes');
        Route::post('/patrones/import',              [AdminPatronesController::class, 'import'])->name('patrones.import');
        Route::patch('/servicios/{servicio}/suspension', [ServicioSuspensionController::class, 'update'])->name('servicios.suspension');
    });

require __DIR__.'/auth.php';
