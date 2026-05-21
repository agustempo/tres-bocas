<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuelleComunidad extends Model
{
    protected $table = 'muelles_comunidad';

    protected $fillable = [
        'user_id',
        'nombre',
        'zona',
        'referencia',
        'confirmaciones',
        'verificado',
    ];

    protected $casts = [
        'verificado'     => 'boolean',
        'confirmaciones' => 'integer',
    ];

    // ── Relaciones ───────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patronesComunidad(): HasMany
    {
        return $this->hasMany(PatronComunidad::class, 'muelle_comunidad_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    /** Only docks with 5+ confirmations, usable as departure point. */
    public function scopeVerificado($query)
    {
        return $query->where('verificado', true);
    }

    // ── Actions ──────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->increment('confirmaciones');
        if (!$this->verificado && $this->fresh()->confirmaciones >= 5) {
            $this->update(['verificado' => true]);
        }
    }
}
