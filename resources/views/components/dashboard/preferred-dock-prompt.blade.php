{{--
    PreferredDockPrompt — inline dock picker for users without a preferred muelle.
    On selection, PATCHes /user/muelle via fetch and reloads the page.
--}}
<div x-data="{
        busca: '',
        open: false,
        guardando: false,
        creandoNuevo: false,
        nuevoNombre: '',
        get filtrados() {
            const lista = (window.deltaMuelles || []);
            if (!this.busca) return lista.slice(0, 8);
            const q = this.busca.toLowerCase();
            return lista.filter(m => m.nombre.toLowerCase().includes(q)).slice(0, 8);
        },
        async seleccionar(muelleId) {
            this.guardando = true;
            this.open = false;
            try {
                await fetch('{{ route('user.muelle.update') }}', {
                    method:  'PATCH',
                    headers: {
                        'Content-Type':     'application/json',
                        'Accept':           'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ muelle_id: muelleId }),
                });
                window.location.reload();
            } catch {
                this.guardando = false;
            }
        },
        async crearNuevo() {
            if (!this.nuevoNombre.trim()) return;
            this.guardando = true;
            try {
                await fetch('{{ route('user.muelle.update') }}', {
                    method:  'PATCH',
                    headers: {
                        'Content-Type':     'application/json',
                        'Accept':           'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ nuevo_muelle_nombre: this.nuevoNombre }),
                });
                window.location.reload();
            } catch {
                this.guardando = false;
            }
        }
    }">

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
        Elegí tu muelle habitual para ver tu próximo barco.
    </p>

    {{-- Search input --}}
    <div x-show="!creandoNuevo" class="relative">
        <input type="text"
               x-model="busca"
               @focus="open = true"
               @input="open = true"
               @keydown.escape="open = false"
               placeholder="Buscá tu muelle…"
               autocomplete="off"
               :disabled="guardando"
               class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700
                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                      placeholder-gray-400 dark:placeholder-gray-500
                      focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                      disabled:opacity-50 transition-colors duration-150">

        <div x-show="open"
             x-cloak
             @click.away="open = false"
             class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800
                    border border-gray-100 dark:border-gray-700
                    rounded-xl shadow-lg max-h-44 overflow-y-auto z-50">
            <template x-for="m in filtrados" :key="m.id">
                <button type="button"
                        @click="seleccionar(m.id)"
                        class="w-full text-left px-4 py-2.5 text-sm
                               text-gray-800 dark:text-gray-200
                               hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-colors">
                    <span x-text="m.nombre"></span>
                    <span x-show="m.zona"
                          x-text="'· ' + m.zona"
                          class="text-xs text-gray-400 dark:text-gray-500 ml-1"></span>
                </button>
            </template>
            <button type="button"
                    @click="creandoNuevo = true; open = false; busca = ''"
                    class="w-full text-left px-4 py-2.5 text-sm font-medium
                           text-teal-600 dark:text-teal-400
                           hover:bg-teal-50 dark:hover:bg-teal-900/20
                           border-t border-gray-100 dark:border-gray-700 transition-colors">
                + Agregar otro muelle
            </button>
        </div>
    </div>

    {{-- New muelle flow --}}
    <div x-show="creandoNuevo" class="space-y-2">
        <input type="text"
               x-model="nuevoNombre"
               @keydown.enter="crearNuevo()"
               placeholder="Nombre del muelle"
               autocomplete="off"
               :disabled="guardando"
               class="w-full px-4 py-2.5 text-sm rounded-xl border border-teal-300 dark:border-teal-700
                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                      placeholder-gray-400 dark:placeholder-gray-500
                      focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-transparent
                      disabled:opacity-50 transition-colors duration-150">
        <div class="flex items-center gap-3">
            <button type="button"
                    @click="crearNuevo()"
                    :disabled="!nuevoNombre.trim() || guardando"
                    class="px-4 py-2 text-sm font-semibold rounded-xl text-white
                           bg-teal-600 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Guardar
            </button>
            <button type="button"
                    @click="creandoNuevo = false; nuevoNombre = ''"
                    class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                Cancelar
            </button>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500">Se agregará pendiente de verificación.</p>
    </div>

    {{-- Saving spinner --}}
    <div x-show="guardando" x-cloak class="flex items-center gap-2 text-sm text-gray-400 mt-2">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        Guardando…
    </div>

</div>
