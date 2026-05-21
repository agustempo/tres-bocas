<?php

namespace App\Http\Controllers;

use App\Models\Avistaje;
use App\Models\ConfirmacionAvistaje;
use App\Models\Muelle;
use App\Models\Patron;
use App\Services\Movilidad\CondicionesNavigacionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HorariosController extends Controller
{
    public function __construct(private CondicionesNavigacionService $condiciones) {}

    public function index(): View
    {
        $user   = auth()->user();
        $muelle = $user->preferredMuelle;

        if (!$muelle) {
            return view('horarios.index', [
                'muelle'          => null,
                'pasadas'         => collect(),
                'recientes'       => collect(),
                'proximas'        => collect(),
                'patronesMana'    => collect(),
                'avisoActivo'     => null,
                'tigreMana'       => collect(),
                'comunidadVuelta' => collect(),
                'comunidadIda'    => collect(),
                'muellesTodos'    => collect(),
            ]);
        }

        return $this->buildView($muelle);
    }

    public function byMuelle(Muelle $muelle): View
    {
        return $this->buildView($muelle);
    }

    private function buildView(Muelle $muelle): View
    {
        $ahora  = now();
        $umbral = $ahora->copy()->subMinutes(20);

        // Today's patrones
        $patronesHoy = Patron::where('muelle_id', $muelle->id)
            ->activo()->publico()->paraHoy()
            ->with('servicio')
            ->orderBy('hora_referencia')
            ->get();

        // Tomorrow's first 5 patrones
        $diaMana     = $ahora->copy()->addDay()->dayOfWeek;
        $tipoDiaMana = in_array($diaMana, [1,2,3,4,5]) ? 'lv' : ($diaMana === 6 ? 'sabado' : 'domingo');

        $patronesMana = Patron::where('muelle_id', $muelle->id)
            ->activo()->publico()
            ->where(function ($q) use ($tipoDiaMana, $diaMana) {
                $q->whereIn('tipo_dia', [$tipoDiaMana, 'todos'])
                  ->orWhere(function ($q2) use ($diaMana) {
                      $q2->whereNull('tipo_dia')->where('dia_semana', $diaMana);
                  });
            })
            ->with('servicio')
            ->orderBy('hora_referencia')
            ->limit(5)
            ->get();

        // Load Tigre departure times so IDA patrones can show "sale de Tigre HH:MM"
        $tigreMuelleId = \App\Models\Muelle::where('nombre', 'Tigre')->value('id');
        $userIsTigre   = $tigreMuelleId && $muelle->id === (int) $tigreMuelleId;

        $tigreHoy  = collect();
        $tigreMana = collect();
        if (!$userIsTigre && $tigreMuelleId) {
            $tigreHoy = Patron::where('muelle_id', $tigreMuelleId)
                ->activo()->publico()->paraHoy()
                ->where('sentido', 'ida')
                ->orderBy('hora_referencia')
                ->get()
                ->groupBy('servicio_id');

            $tigreMana = Patron::where('muelle_id', $tigreMuelleId)
                ->activo()->publico()
                ->where(function ($q) use ($tipoDiaMana, $diaMana) {
                    $q->whereIn('tipo_dia', [$tipoDiaMana, 'todos'])
                      ->orWhere(function ($q2) use ($diaMana) {
                          $q2->whereNull('tipo_dia')->where('dia_semana', $diaMana);
                      });
                })
                ->where('sentido', 'ida')
                ->orderBy('hora_referencia')
                ->get()
                ->groupBy('servicio_id');
        }

        // Closure: find the Tigre departure patron for a given IDA patron
        $findHoraTigre = function (Patron $patron, \Illuminate\Support\Collection $tigreGroup): ?Carbon {
            $list = $tigreGroup->get($patron->servicio_id) ?? collect();
            $match = $list
                ->filter(fn ($t) => $t->hora_referencia <= $patron->hora_referencia)
                ->sortByDesc('hora_referencia')
                ->first();
            return $match ? Carbon::today()->setTimeFromTimeString($match->hora_referencia) : null;
        };

        $patronIds = $patronesHoy->pluck('id');

        // Active incident avistajes per patron (last 3 hours, today)
        $incidentes = Avistaje::whereIn('patron_id', $patronIds)
            ->whereIn('tipo', ['demorado', 'cancelado', 'no_paro', 'problema_muelle', 'otro'])
            ->whereDate('hora_evento', today())
            ->where('hora_evento', '>=', $ahora->copy()->subHours(3))
            ->with(['user', 'confirmacionesRelacion'])
            ->orderByDesc('confirmaciones')
            ->get()
            ->groupBy('patron_id');

        // Positive avistajes per patron today
        $positivos = Avistaje::whereIn('patron_id', $patronIds)
            ->whereIn('tipo', ['paso', 'embarco'])
            ->whereDate('hora_evento', today())
            ->with('confirmacionesRelacion')
            ->get()
            ->groupBy('patron_id');

        $userId = auth()->id();

        $salidas = $patronesHoy->map(function (Patron $patron) use ($umbral, $ahora, $incidentes, $positivos, $userId, $findHoraTigre, $tigreHoy) {
            $hora       = Carbon::today()->setTimeFromTimeString($patron->hora_referencia);
            $esPasado   = $hora->lt($umbral);
            $esReciente = !$esPasado && $hora->lte($ahora);
            $esProximo  = !$esPasado && !$esReciente && $hora->copy()->subMinutes($patron->ventana_min)->lte($ahora);

            $min      = (int) $ahora->diffInMinutes($hora, false);
            $minAtras = $esReciente ? max(0, (int) $ahora->diffInMinutes($hora)) : 0;

            $avistajeActivo  = $incidentes->get($patron->id)?->first();
            $positiveGroup   = $positivos->get($patron->id) ?? collect();
            $nConfirmaciones = $positiveGroup->sum(fn ($av) => 1 + $av->confirmaciones);

            $yaConfirmo = $userId && $positiveGroup->contains(function ($av) use ($userId) {
                return $av->confirmacionesRelacion->contains('user_id', $userId);
            });

            $horaTigre  = ($patron->sentido === 'ida')
                ? $findHoraTigre($patron, $tigreHoy)
                : null;
            $miReaccion = session("departure_reaction_{$patron->id}", '');

            return compact('patron', 'hora', 'esPasado', 'esReciente', 'esProximo', 'min', 'minAtras',
                           'avistajeActivo', 'nConfirmaciones', 'yaConfirmo', 'horaTigre', 'miReaccion');
        });

        $pasadas   = $salidas->filter(fn ($s) => $s['esPasado'])->values();
        $recientes = $salidas->filter(fn ($s) => $s['esReciente'])->values();
        $proximas  = $salidas->filter(fn ($s) => !$s['esPasado'] && !$s['esReciente'])->values();

        // Banner: most confirmed active incident
        $avisoActivo = $incidentes->flatten()->sortByDesc('confirmaciones')->first();

        // Community-proposed schedules for today at this muelle
        $comunidadHoy = \App\Models\PatronComunidad::where('muelle_id', $muelle->id)
            ->paraHoy()
            ->with('user')
            ->orderBy('hora_referencia')
            ->get()
            ->map(function ($patron) {
                $miReaccion = session("comunidad_reaction_{$patron->id}", '');
                return compact('patron', 'miReaccion');
            });

        $comunidadVuelta = $comunidadHoy->filter(fn ($s) => $s['patron']->sentido === 'vuelta')->values();
        $comunidadIda    = $comunidadHoy->filter(fn ($s) => $s['patron']->sentido === 'ida')->values();

        // Muelle list for the "add schedule" form muelle picker
        $muellesTodos = \App\Models\Muelle::activo()->orderBy('nombre')->get(['id', 'nombre', 'zona']);

        return view('horarios.index', compact(
            'muelle', 'pasadas', 'recientes', 'proximas', 'patronesMana', 'avisoActivo', 'tigreMana',
            'comunidadVuelta', 'comunidadIda', 'muellesTodos'
        ));
    }

    public function confirmarSalida(Request $request, Patron $patron): RedirectResponse
    {
        $userId = auth()->id();
        $muelle = auth()->user()->preferredMuelle;

        if (!$muelle) {
            return redirect()->route('horarios.index');
        }

        // Find or create a 'paso' avistaje for this patron today
        $avistaje = Avistaje::where('patron_id', $patron->id)
            ->where('muelle_id', $muelle->id)
            ->whereIn('tipo', ['paso', 'embarco'])
            ->whereDate('hora_evento', today())
            ->orderByDesc('confirmaciones')
            ->first();

        if (!$avistaje) {
            $snapshot = $this->condiciones->snapshotParaAvistaje();
            $hora     = Carbon::today()->setTimeFromTimeString($patron->hora_referencia);

            $avistaje = Avistaje::create([
                'patron_id'      => $patron->id,
                'muelle_id'      => $muelle->id,
                'servicio_id'    => $patron->servicio_id,
                'user_id'        => $userId,
                'tipo'           => 'paso',
                'hora_evento'    => $hora,
                'sentido'        => in_array($patron->sentido, ['ida', 'vuelta']) ? $patron->sentido : null,
                'confirmaciones' => 0,
                'nivel_marea'    => $snapshot['nivel_marea'],
                'viento_kmh'     => $snapshot['viento_kmh'],
                'condicion_clima'=> $snapshot['condicion_clima'],
            ]);
        }

        // Don't allow self-confirm
        if ($avistaje->user_id !== $userId) {
            $yaConfirmado = ConfirmacionAvistaje::where('avistaje_id', $avistaje->id)
                ->where('user_id', $userId)
                ->exists();

            if (!$yaConfirmado) {
                ConfirmacionAvistaje::create(['avistaje_id' => $avistaje->id, 'user_id' => $userId]);
                $avistaje->increment('confirmaciones');
            }
        }

        return redirect()->route('horarios.index');
    }

    public function reaccionar(Request $request, Patron $patron): \Illuminate\Http\JsonResponse
    {
        $tipo = $request->input('tipo');
        if (!in_array($tipo, ['positivo', 'negativo'])) {
            return response()->json(['ok' => false], 422);
        }

        $sessionKey = "departure_reaction_{$patron->id}";
        if (session()->has($sessionKey)) {
            return response()->json(['ok' => true]);
        }

        session([$sessionKey => $tipo]);

        if ($tipo === 'positivo' && auth()->check()) {
            $user   = auth()->user();
            $muelle = $user->preferredMuelle;

            if ($muelle) {
                $avistaje = Avistaje::where('patron_id', $patron->id)
                    ->where('muelle_id', $muelle->id)
                    ->whereIn('tipo', ['paso', 'embarco'])
                    ->whereDate('hora_evento', today())
                    ->orderByDesc('confirmaciones')
                    ->first();

                if (!$avistaje) {
                    $snapshot = $this->condiciones->snapshotParaAvistaje();
                    $hora     = Carbon::today()->setTimeFromTimeString($patron->hora_referencia);
                    Avistaje::create([
                        'patron_id'       => $patron->id,
                        'muelle_id'       => $muelle->id,
                        'servicio_id'     => $patron->servicio_id,
                        'user_id'         => $user->id,
                        'tipo'            => 'paso',
                        'hora_evento'     => $hora,
                        'sentido'         => in_array($patron->sentido, ['ida', 'vuelta']) ? $patron->sentido : null,
                        'confirmaciones'  => 0,
                        'nivel_marea'     => $snapshot['nivel_marea'],
                        'viento_kmh'      => $snapshot['viento_kmh'],
                        'condicion_clima' => $snapshot['condicion_clima'],
                    ]);
                } elseif ($avistaje->user_id !== $user->id) {
                    $yaConfirmado = ConfirmacionAvistaje::where('avistaje_id', $avistaje->id)
                        ->where('user_id', $user->id)
                        ->exists();
                    if (!$yaConfirmado) {
                        ConfirmacionAvistaje::create(['avistaje_id' => $avistaje->id, 'user_id' => $user->id]);
                        $avistaje->increment('confirmaciones');
                    }
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
