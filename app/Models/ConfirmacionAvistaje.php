<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfirmacionAvistaje extends Model
{
    protected $table = 'confirmaciones_avistaje';

    public $timestamps = false;

    protected $fillable = [
        'avistaje_id',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function avistaje(): BelongsTo
    {
        return $this->belongsTo(Avistaje::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
