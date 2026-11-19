<div x-data="markerNavigation" wire:ignore>
    <aside x-show="message" x-cloak role="alert" class="fixed bottom-24 right-4 z-50 max-w-sm rounded-xl border border-blue-200 bg-white p-4 text-slate-800 shadow-lg">
        <p x-text="message"></p>
        <button type="button" @click="message = ''" class="mt-2 min-h-11 rounded-lg border px-4 font-semibold focus-visible:ring-2 focus-visible:ring-blue-700">Cerrar</button>
    </aside>
    <div x-show="canReturn" x-cloak class="fixed bottom-4 right-4 z-50">
        <button type="button" @click="returnToSource()" class="min-h-11 rounded-full bg-blue-700 px-5 py-3 font-semibold text-white shadow-lg hover:bg-blue-800 focus-visible:ring-4 focus-visible:ring-blue-300">↩ Regresar a la sección</button>
    </div>
</div>
