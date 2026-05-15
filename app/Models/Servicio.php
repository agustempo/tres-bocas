<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $fillable = [
        'nombre',
        'slug',
        'operador',
        'tipo',
        'descripcion',
        'contacto',
        'activo',
        'verificado',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'verificado' => 'boolean',
    ];

    public function muelles(): BelongsToMany
    {
        return $this->belongsToMany(Muelle::class, 'muelle_servicio')
            ->withPivot(['orden', 'sentido'])
            ->orderBy('muelle_servicio.orden');
    }

    public function patrones(): HasMany
    {
        return $this->hasMany(Patron::class);
    }

    public function avistajes(): HasMany
    {
        return $this->hasMany(Avistaje::class);
    }

    public function alertasActivas(): HasMany
    {
        return $this->hasMany(AlertaServicio::class)->where(function ($q) {
            $q->whereNull('valida_hasta')->orWhere('valida_hasta', '>', now());
        })->where('valida_desde', '<=', now());
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function tipoLabel(): string
    {
        return match($this->tipo) {
            'lancha_colectiva' => __('movilidad.tipo_lancha_colectiva'),
            'remise_fluvial'   => __('movilidad.tipo_remise_fluvial'),
            'carga'            => __('movilidad.tipo_carga'),
            'especial'         => __('movilidad.tipo_especial'),
            default            => $this->tipo,
        };
    }
}
