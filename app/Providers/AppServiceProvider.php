<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Models\Review;
use App\Observers\ReviewObserver;
use App\Policies\ReservationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Review::observe(ReviewObserver::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
    }
}
