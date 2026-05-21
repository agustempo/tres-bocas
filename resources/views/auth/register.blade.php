<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Muelle habitual -->
        <div class="mt-4" x-data="{
            creandoNuevo: false,
            get muelles() { return window.deltaMuelles || []; }
        }">
            <x-input-label for="preferred_muelle_id" value="¿Cuál es tu muelle habitual? (opcional)" />
            <div x-show="!creandoNuevo">
                <select id="preferred_muelle_id"
                        name="preferred_muelle_id"
                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm
                               focus:border-teal-500 focus:ring-teal-500 dark:bg-gray-900
                               dark:border-gray-700 dark:text-gray-100">
                    <option value="">— Ninguno por ahora —</option>
                    @foreach(\App\Models\Muelle::activo()->orderBy('orden')->orderBy('nombre')->get() as $m)
                        <option value="{{ $m->id }}" @selected(old('preferred_muelle_id') == $m->id)>
                            {{ $m->nombre }}{{ $m->zona ? ' · ' . $m->zona : '' }}
                        </option>
                    @endforeach
                </select>
                <button type="button"
                        @click="creandoNuevo = true"
                        class="mt-1 text-xs text-teal-600 hover:underline">
                    + Agregar otro muelle
                </button>
            </div>
            <div x-show="creandoNuevo" class="mt-1 space-y-1">
                <input type="hidden" name="preferred_muelle_id" value="nuevo">
                <x-text-input id="nuevo_muelle_nombre"
                              name="nuevo_muelle_nombre"
                              class="block w-full"
                              type="text"
                              placeholder="Nombre del muelle"
                              :value="old('nuevo_muelle_nombre')" />
                <p class="text-xs text-gray-400">Se agregará pendiente de verificación.</p>
                <button type="button"
                        @click="creandoNuevo = false"
                        class="text-xs text-gray-400 hover:text-gray-600">Cancelar</button>
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
