<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatronComunidad extends Model
{
    protected $table = 'patrones_comunidad';

    protected $fillable = [
        'user_id',
        'muelle_id',
        'muelle_comunidad_id',
        'destino',
        'empresa',
        'hora_referencia',
        'ventana_min',
        'sentido',
        'recurrencia',
        'fecha_unica',
        'confirmaciones',
        'verificado',
    ];

    protected $casts = [
        'verificado'     => 'boolean',
        'confirmaciones' => 'integer',
        'ventana_min'    => 'integer',
        'fecha_unica'    => 'date',
    ];

    // ── Relaciones ───────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function muelle(): BelongsTo
    {
        return $this->belongsTo(Muelle::class);
    }

    public function muelleComunidad(): BelongsTo
    {
        return $this->belongsTo(MuelleComunidad::class, 'muelle_comunidad_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * Patrones that apply today based on recurrence.
     */
    public function scopeParaHoy($query)
    {
        $hoy   = now()->dayOfWeek; // 0=Sunday … 6=Saturday
        $esLv  = in_array($hoy, [1, 2, 3, 4, 5]);
        $esSab = $hoy === 6;
        $esDom = $hoy === 0;

        return $query->where(function ($q) use ($esLv, $esSab, $esDom) {
            $q->where('recurrencia', 'diario');

            if ($esLv)  $q->orWhere('recurrencia', 'lv');
            if ($esSab) $q->orWhere('recurrencia', 'sabado');
            if ($esDom) $q->orWhere('recurrencia', 'domingo');

            // legacy compat for old 'fds' records
            if ($esSab || $esDom) $q->orWhere('recurrencia', 'fds');

            $q->orWhere(fn ($q2) =>
                $q2->where('recurrencia', 'unico')->whereDate('fecha_unica', today())
            );
        });
    }

    // ── Actions ──────────────────────────────────────────────────

    /**
     * Register a community confirmation (👍).
     * Auto-verifies at 5 confirmations.
     */
    public function confirmar(): void
    {
        $this->increment('confirmaciones');
        if (!$this->verificado && $this->fresh()->confirmaciones >= 5) {
            $this->update(['verificado' => true]);
        }
    }

    // ── Presentación ─────────────────────────────────────────────

    /** Human-readable recurrence label. */
    public function recurrenciaLabel(): string
    {
        return match($this->recurrencia) {
            'diario'  => __('schedule.recurrencia_diario'),
            'lv'      => __('schedule.recurrencia_lv'),
            'sabado'  => __('schedule.recurrencia_sabado'),
            'domingo' => __('schedule.recurrencia_domingo'),
            'fds'     => __('schedule.recurrencia_fds'),
            'unico'   => __('schedule.recurrencia_unico'),
            default   => $this->recurrencia,
        };
    }

    /** Display name: empresa if set, otherwise destino. */
    public function nombreDisplay(): string
    {
        return $this->empresa ?: $this->destino;
    }

    /** Progress toward verification (0–5). */
    public function progresoConfianza(): int
    {
        return min(5, $this->confirmaciones);
    }
}
