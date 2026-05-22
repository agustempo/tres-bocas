<?php

namespace App\Http\Controllers;

use App\Models\Avistaje;
use App\Models\Listing;
use App\Models\Review;
use App\Services\Movilidad\MobilidadService;
use App\Services\TideService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private TideService $tideService,
        private MobilidadService $movilidad,
    ) {}

    public function index(): View
    {
        $tide = $this->tideService->getData();

        $totalListings         = 0;
        $publishedListings     = 0;
        $pendingReviews        = 0;
        $myListings            = null;
        $muelle                = null;
        $proximoPaso           = null;
        $servicioPrincipal     = null;
        $avistajeProximo       = null;
        $confirmacionesProximo = 0;
        $miReaccion            = '';

        $user = auth()->user();

        if ($user) {
            if ($user->isAdmin()) {
                $totalListings     = Listing::count();
                $publishedListings = Listing::where('status', 'published')->count();
                $pendingReviews    = Review::where('approved', false)->count();
            } else {
                $totalListings     = $user->listings()->count();
                $publishedListings = $user->listings()->where('status', 'published')->count();
                $pendingReviews    = Review::whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                                           ->where('approved', false)
                                           ->count();
                $myListings        = $user->listings()->with('category')->latest()->get();
            }

            $muelle = $user->preferredMuelle;

            if ($muelle) {
                $servicioPrincipal = $muelle->servicios()->where('activo', true)->first();

                if ($servicioPrincipal) {
                    $proximoPaso = $this->movilidad->estimarProximoPaso($muelle->id, $servicioPrincipal->id);

                    if ($proximoPaso && ($patron = $proximoPaso['patron'] ?? null)) {
                        $avistajeProximo = Avistaje::where('patron_id', $patron->id)
                            ->whereIn('tipo', ['demorado', 'cancelado', 'no_paro', 'problema_muelle', 'otro'])
                            ->whereDate('hora_evento', today())
                            ->where('hora_evento', '>=', now()->subHours(3))
                            ->orderByDesc('confirmaciones')
                            ->first();

                        $confirmacionesProximo = Avistaje::where('patron_id', $patron->id)
                            ->whereIn('tipo', ['paso', 'embarco'])
                            ->whereDate('hora_evento', today())
                            ->sum('confirmaciones');

                        $miReaccion = session("departure_reaction_{$patron->id}", '');
                    }
                }
            }
        }

        return view('dashboard', compact(
            'totalListings',
            'publishedListings',
            'pendingReviews',
            'myListings',
            'tide',
            'muelle',
            'proximoPaso',
            'servicioPrincipal',
            'avistajeProximo',
            'confirmacionesProximo',
            'miReaccion',
        ));
    }
}
