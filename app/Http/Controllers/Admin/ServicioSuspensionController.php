<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioSuspensionController extends Controller
{
    public function update(Request $request, Servicio $servicio)
    {
        $request->validate([
            'suspendido'        => ['required', 'boolean'],
            'suspension_motivo' => ['nullable', 'string', 'max:200'],
        ]);

        $servicio->update([
            'suspendido'        => $request->boolean('suspendido'),
            'suspension_motivo' => $request->boolean('suspendido') ? $request->suspension_motivo : null,
        ]);

        $msg = $request->boolean('suspendido')
            ? 'Servicio suspendido. Se mostrará alerta roja en el panel.'
            : 'Servicio reactivado.';

        return back()->with('success', $msg);
    }
}
