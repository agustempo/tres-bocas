<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Muelle;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private function resolvePreferredMuelle(Request $request): ?int
    {
        $muelleId   = $request->input('preferred_muelle_id');
        $nuevoNombre = trim($request->input('nuevo_muelle_nombre', ''));

        // Existing muelle selected
        if ($muelleId && $muelleId !== 'nuevo' && is_numeric($muelleId)) {
            if (Muelle::where('id', $muelleId)->exists()) {
                return (int) $muelleId;
            }
        }

        // User wants to create a new muelle
        if ($nuevoNombre !== '') {
            $slug = Str::slug($nuevoNombre);
            if (!$slug) return null;

            // Avoid duplicates by normalised nombre
            $existing = Muelle::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nuevoNombre)])->first();
            if ($existing) return $existing->id;

            // Ensure unique slug
            $baseSlug = $slug;
            $i = 2;
            while (Muelle::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            $muelle = Muelle::create([
                'nombre' => $nuevoNombre,
                'slug'   => $slug,
                'activo' => false,
            ]);

            return $muelle->id;
        }

        return null;
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $preferredMuelleId = $this->resolvePreferredMuelle($request);

        $user = User::create([
            'name'                => $request->name,
            'email'               => $request->email,
            'password'            => Hash::make($request->password),
            'preferred_muelle_id' => $preferredMuelleId,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
