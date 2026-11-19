<div x-data="markerNavigation" data-marker-navigation wire:ignore>
    <div x-show="busy" x-cloak data-marker-loading role="dialog" aria-modal="true" aria-label="Abriendo la sección" class="fixed inset-0 z-[10070] flex items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center text-slate-800 shadow-xl">
            <div role="status" aria-live="polite" aria-atomic="true">
                <svg class="mx-auto mb-4 h-9 w-9 animate-spin text-blue-700 motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25" />
                    <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
                <p class="text-lg font-semibold">Abriendo la sección…</p>
                <p class="mt-2 text-sm text-slate-600">Te llevaremos al texto del enlace. Espera un momento.</p>
            </div>
            <button type="button" @click="cancelNavigation()" class="mt-5 min-h-11 rounded-lg border border-slate-300 px-5 font-semibold text-slate-700 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-blue-700">Cancelar</button>
        </div>
    </div>
    <aside x-show="message" x-cloak role="alert" class="fixed bottom-24 right-4 z-50 max-w-sm rounded-xl border border-blue-200 bg-white p-4 text-slate-800 shadow-lg">
        <p x-text="message"></p>
        <button type="button" @click="message = ''" class="mt-2 min-h-11 rounded-lg border px-4 font-semibold focus-visible:ring-2 focus-visible:ring-blue-700">Cerrar</button>
    </aside>
    <div x-show="canReturn" x-cloak class="fixed bottom-4 right-4 z-50">
        <button type="button" @click="returnToSource()" class="min-h-11 rounded-full bg-blue-700 px-5 py-3 font-semibold text-white shadow-lg hover:bg-blue-800 focus-visible:ring-4 focus-visible:ring-blue-300">↩ Regresar a la sección</button>
    </div>
</div>
