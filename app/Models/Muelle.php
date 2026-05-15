<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Muelle extends Model
{
    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'rio',
        'zona',
        'latitud',
        'longitud',
        'tipo_canal',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'muelle_servicio')
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

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaServicio::class)->where(function ($q) {
            $q->whereNull('valida_hasta')->orWhere('valida_hasta', '>', now());
        })->where('valida_desde', '<=', now());
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
