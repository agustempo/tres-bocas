<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patron extends Model
{
    protected $table = 'patrones';

    protected $fillable = [
        'servicio_id', 'muelle_id', 'dia_semana', 'tipo_dia',
        'hora_referencia', 'ventana_min', 'sentido', 'temporada',
        'notas', 'activo', 'fuente', 'visibilidad',
        'fuente_url', 'validado_at', 'notas_admin',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'dia_semana'  => 'integer',
        'ventana_min' => 'integer',
        'validado_at' => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function avistajes(): HasMany { return $this->hasMany(Avistaje::class); }
    public function servicio(): BelongsTo { return $this->belongsTo(Servicio::class); }
    public function muelle(): BelongsTo { return $this->belongsTo(Muelle::class); }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActivo($query) { return $query->where('activo', true); }
    public function scopePublico($query) { return $query->where('visibilidad', 'publico'); }

    public function scopeParaHoy($query)
    {
        $hoy    = now()->dayOfWeek;
        $tipoDia = in_array($hoy, [1,2,3,4,5]) ? 'lv' : ($hoy === 6 ? 'sabado' : 'domingo');

        return $query->where(function ($q) use ($hoy, $tipoDia) {
            $q->whereIn('tipo_dia', [$tipoDia, 'todos'])
              ->orWhere(function ($q2) use ($hoy) {
                  $q2->whereNull('tipo_dia')->where('dia_semana', $hoy);
              });
        });
    }

    // ── Presentación ────────────────────────────────────────────

    public function tipoDiaCalculado(): string
    {
        if ($this->tipo_dia) return $this->tipo_dia;
        if ($this->dia_semana === null) return 'todos';
        return match(true) {
            in_array($this->dia_semana, [1,2,3,4,5]) => 'lv',
            $this->dia_semana === 6                  => 'sabado',
            default                                  => 'domingo',
        };
    }

    public function diaLabel(): string
    {
        return match($this->tipoDiaCalculado()) {
            'lv'     => 'L–V',
            'sabado' => 'Sáb',
            'domingo'=> 'Dom',
            default  => 'Todos los días',
        };
    }

    public function horaConVentana(): string
    {
        return __('movilidad.patron_hora_ventana', [
            'hora'    => substr($this->hora_referencia, 0, 5),
            'ventana' => $this->ventana_min,
        ]);
    }

    public function fuenteLabel(): string
    {
        return match($this->fuente) {
            'oficial'   => 'Oficial',
            'comunidad' => 'Comunidad',
            default     => 'Estimado',
        };
    }

    public function fuenteColor(): string
    {
        return match($this->fuente) {
            'oficial'   => 'blue',
            'comunidad' => 'amber',
            default     => 'gray',
        };
    }

    public function necesitaRevision(): bool
    {
        if (! $this->validado_at) return true;
        $limite = match($this->fuente) {
            'oficial' => 60, 'comunidad' => 14, default => 30,
        };
        return $this->validado_at->diffInDays(now()) > $limite;
    }
}
