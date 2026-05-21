<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avistaje extends Model
{
    protected $fillable = [
        'servicio_id',
        'patron_id',
        'muelle_id',
        'user_id',
        'tipo',
        'hora_evento',
        'sentido',
        'notas',
        'nivel_marea',
        'viento_kmh',
        'condicion_clima',
        'confirmaciones',
    ];

    protected $casts = [
        'hora_evento' => 'datetime',
        'confirmaciones' => 'integer',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function muelle(): BelongsTo
    {
        return $this->belongsTo(Muelle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmacionesRelacion(): HasMany
    {
        return $this->hasMany(ConfirmacionAvistaje::class);
    }

    public function tipoLabel(): string
    {
        return match($this->tipo) {
            'paso'             => __('movilidad.avistaje_paso'),
            'embarco'          => __('movilidad.avistaje_embarco'),
            'no_paro'          => __('movilidad.avistaje_no_paro'),
            'cancelado'        => __('movilidad.avistaje_cancelado'),
            'demorado'         => __('movilidad.avistaje_demorado'),
            'problema_muelle'  => __('movilidad.avistaje_problema_muelle'),
            'otro'             => __('movilidad.avistaje_otro'),
            default            => $this->tipo,
        };
    }

    public function tipoIcono(): string
    {
        return match($this->tipo) {
            'paso'            => '✓',
            'embarco'         => '✓',
            'no_paro'         => '✗',
            'cancelado'       => '✗',
            'demorado'        => '⏱',
            'problema_muelle' => '⚠',
            'otro'            => '⚠',
            default           => '•',
        };
    }

    public function esIncidente(): bool
    {
        return in_array($this->tipo, ['demorado', 'cancelado', 'no_paro', 'problema_muelle', 'otro']);
    }

    public function esFresco(): bool
    {
        return $this->hora_evento->diffInMinutes(now()) < 240;
    }
}
