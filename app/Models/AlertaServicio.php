<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaServicio extends Model
{
    protected $table = 'alertas_servicio';

    protected $fillable = [
        'servicio_id',
        'tipo',
        'descripcion',
        'valida_desde',
        'valida_hasta',
        'creada_por',
    ];

    protected $casts = [
        'valida_desde' => 'datetime',
        'valida_hasta' => 'datetime',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function tipoLabel(): string
    {
        return match($this->tipo) {
            'suspension'       => __('movilidad.alerta_suspension'),
            'demora_general'   => __('movilidad.alerta_demora_general'),
            'ruta_alternativa' => __('movilidad.alerta_ruta_alternativa'),
            default            => $this->tipo,
        };
    }

    public function scopeActivas($query)
    {
        return $query->where('valida_desde', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valida_hasta')->orWhere('valida_hasta', '>', now());
            });
    }
}
