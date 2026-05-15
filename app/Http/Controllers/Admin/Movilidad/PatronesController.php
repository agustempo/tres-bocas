<?php

namespace App\Http\Controllers\Admin\Movilidad;

use App\Http\Controllers\Controller;
use App\Models\Avistaje;
use App\Models\Patron;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatronesController extends Controller
{
    // ── Crear ────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'muelle_id'       => ['required', 'integer', 'exists:muelles,id'],
            'servicio_id'     => ['required', 'integer', 'exists:servicios,id'],
            'hora'            => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'sentido'         => ['required', 'in:ida,vuelta'],
            'tipo_dia'        => ['required', 'in:lv,sabado,domingo,todos'],
            'fuente'          => ['nullable', 'in:oficial,comunidad,estimado'],
            'fuente_url'      => ['nullable', 'string', 'max:500'],
        ]);

        // Usamos tipo_dia en lugar de dia_semana para nuevos registros
        $diasMap = ['lv' => [1,2,3,4,5], 'sabado' => [6], 'domingo' => [0], 'todos' => [null]];
        $dias    = $diasMap[$data['tipo_dia']];

        $created = [];
        foreach ($dias as $dia) {
            $patron = Patron::create([
                'muelle_id'       => $data['muelle_id'],
                'servicio_id'     => $data['servicio_id'],
                'dia_semana'      => $dia,
                'tipo_dia'        => $data['tipo_dia'],
                'hora_referencia' => $data['hora'] . ':00',
                'sentido'         => $data['sentido'],
                'fuente'          => $data['fuente'] ?? 'comunidad',
                'fuente_url'      => $data['fuente_url'] ?? null,
                'visibilidad'     => 'publico',
                'ventana_min'     => 15,
                'temporada'       => 'todo',
                'activo'          => true,
                'validado_at'     => now(),
            ]);
            $created[] = $patron->id;
        }

        return response()->json([
            'ok'   => true,
            'ids'  => $created,
            'hora' => $data['hora'],
        ]);
    }

    // ── Actualizar (opera sobre el grupo de IDs) ─────────────────

    public function update(Request $request, Patron $patron): JsonResponse
    {
        $data = $request->validate([
            'ids'          => ['nullable', 'array'],
            'ids.*'        => ['integer'],
            'hora'         => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'fuente'       => ['nullable', 'in:oficial,comunidad,estimado'],
            'visibilidad'  => ['nullable', 'in:publico,oculto,experimental'],
            'notas_admin'  => ['nullable', 'string', 'max:1000'],
            'fuente_url'   => ['nullable', 'string', 'max:500'],
        ]);

        // Restringir IDs al mismo muelle/servicio que el patrón referencia
        $ids = $this->resolveIds($patron, $request->input('ids', []));

        $update = [];
        if (isset($data['hora']))        $update['hora_referencia'] = $data['hora'] . ':00';
        if (isset($data['fuente']))      $update['fuente']          = $data['fuente'];
        if (isset($data['visibilidad'])) $update['visibilidad']     = $data['visibilidad'];
        if (isset($data['notas_admin'])) $update['notas_admin']     = $data['notas_admin'];
        if (isset($data['fuente_url']))  $update['fuente_url']      = $data['fuente_url'];

        if (! empty($update)) {
            Patron::whereIn('id', $ids)->update($update);
        }

        return response()->json(['ok' => true, 'ids' => $ids]);
    }

    // ── Validar (marcar como revisado hoy) ──────────────────────

    public function validar(Request $request, Patron $patron): JsonResponse
    {
        $ids = $this->resolveIds($patron, $request->input('ids', []));
        Patron::whereIn('id', $ids)->update(['validado_at' => now()]);
        return response()->json(['ok' => true, 'validado_at' => now()->toDateTimeString()]);
    }

    // ── Validar bulk (todos los de un muelle/servicio/tipoDia) ──

    public function validarBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'muelle_id'   => ['required', 'integer', 'exists:muelles,id'],
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'tipo_dia'    => ['nullable', 'in:lv,sabado,domingo,todos'],
        ]);

        $q = Patron::where('muelle_id', $data['muelle_id'])
            ->where('servicio_id', $data['servicio_id'])
            ->where('activo', true);

        if (! empty($data['tipo_dia'])) {
            $td = $data['tipo_dia'];
            $q->where(function ($q) use ($td) {
                $q->where('tipo_dia', $td)
                  ->orWhere(function ($q2) use ($td) {
                      $diasMap = ['lv'=>[1,2,3,4,5],'sabado'=>[6],'domingo'=>[0]];
                      if (isset($diasMap[$td])) {
                          $q2->whereNull('tipo_dia')->whereIn('dia_semana', $diasMap[$td]);
                      }
                  });
            });
        }

        $count = $q->update(['validado_at' => now()]);

        return response()->json(['ok' => true, 'count' => $count]);
    }

    // ── Eliminar (ocultar primero, delete en segunda instancia) ──

    public function destroy(Request $request, Patron $patron): JsonResponse
    {
        $ids = $this->resolveIds($patron, $request->input('ids', []));
        Patron::whereIn('id', $ids)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Importar planilla (bulk create desde texto) ───────────────

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'muelle_id'   => ['required', 'integer', 'exists:muelles,id'],
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'sentido'     => ['required', 'in:ida,vuelta'],
            'tipo_dia'    => ['required', 'in:lv,sabado,domingo,todos'],
            'fuente'      => ['nullable', 'in:oficial,comunidad,estimado'],
            'fuente_url'  => ['nullable', 'string', 'max:500'],
            'horas'       => ['required', 'array', 'min:1', 'max:50'],
            'horas.*'     => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $diasMap  = ['lv'=>[1,2,3,4,5],'sabado'=>[6],'domingo'=>[0],'todos'=>[null]];
        $dias     = $diasMap[$data['tipo_dia']];
        $fuente   = $data['fuente'] ?? 'comunidad';
        $created  = 0;
        $skipped  = 0;

        foreach ($data['horas'] as $hora) {
            foreach ($dias as $dia) {
                $exists = Patron::where('muelle_id', $data['muelle_id'])
                    ->where('servicio_id', $data['servicio_id'])
                    ->where('sentido', $data['sentido'])
                    ->where('hora_referencia', $hora . ':00')
                    ->where(fn($q) => $dia === null
                        ? $q->whereNull('dia_semana')
                        : $q->where('dia_semana', $dia))
                    ->exists();

                if ($exists) { $skipped++; continue; }

                Patron::create([
                    'muelle_id'       => $data['muelle_id'],
                    'servicio_id'     => $data['servicio_id'],
                    'dia_semana'      => $dia,
                    'tipo_dia'        => $data['tipo_dia'],
                    'hora_referencia' => $hora . ':00',
                    'sentido'         => $data['sentido'],
                    'fuente'          => $fuente,
                    'fuente_url'      => $data['fuente_url'] ?? null,
                    'visibilidad'     => 'publico',
                    'ventana_min'     => 15,
                    'temporada'       => 'todo',
                    'activo'          => true,
                    'validado_at'     => now(),
                ]);
                $created++;
            }
        }

        return response()->json(['ok' => true, 'created' => $created, 'skipped' => $skipped]);
    }

    // ── Avistajes de la comunidad para un patrón ────────────────

    public function avistajes(Request $request, Patron $patron): JsonResponse
    {
        $ids  = $this->resolveIds($patron, $request->query('ids', []));
        $hora = substr($patron->hora_referencia, 0, 8);
        $ref  = Carbon::createFromFormat('H:i:s', $hora);
        $desde = $ref->copy()->subMinutes(30)->format('H:i:s');
        $hasta = $ref->copy()->addMinutes(30)->format('H:i:s');

        $avistajes = Avistaje::where(function ($q) use ($ids, $patron, $desde, $hasta) {
            $q->whereIn('patron_id', $ids)
              ->orWhere(function ($q2) use ($patron, $desde, $hasta) {
                  $q2->where('muelle_id', $patron->muelle_id)
                     ->where('servicio_id', $patron->servicio_id)
                     ->whereNull('patron_id')
                     ->whereRaw("TIME(hora_evento) BETWEEN ? AND ?", [$desde, $hasta]);
                  if ($patron->sentido && $patron->sentido !== 'ambos') {
                      $q2->where(fn ($q3) => $q3->where('sentido', $patron->sentido)->orWhereNull('sentido'));
                  }
              });
        })
        ->with('user:id,name')
        ->orderBy('hora_evento', 'desc')
        ->limit(30)
        ->get();

        return response()->json($avistajes->map(fn ($a) => [
            'id'             => $a->id,
            'tipo'           => $a->tipo,
            'tipo_label'     => $a->tipoLabel(),
            'tipo_icono'     => $a->tipoIcono(),
            'notas'          => $a->notas,
            'sentido'        => $a->sentido,
            'confirmaciones' => $a->confirmaciones,
            'hora_label'     => $this->formatHoraLabel($a->hora_evento),
            'user_name'      => $a->user?->name,
        ]));
    }

    private function formatHoraLabel(Carbon $dt): string
    {
        $diff = (int) now()->diffInDays($dt, false);
        if ($diff === 0) return 'hoy ' . $dt->format('H:i');
        if ($diff === -1) return 'ayer ' . $dt->format('H:i');
        return $dt->format('d/m') . ' ' . $dt->format('H:i');
    }

    // ── Helper ───────────────────────────────────────────────────

    private function resolveIds(Patron $reference, array $requestedIds): array
    {
        if (empty($requestedIds)) {
            return [$reference->id];
        }

        return Patron::whereIn('id', $requestedIds)
            ->where('muelle_id', $reference->muelle_id)
            ->where('servicio_id', $reference->servicio_id)
            ->pluck('id')
            ->toArray();
    }
}
