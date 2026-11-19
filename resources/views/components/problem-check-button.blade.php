@props(['action', 'label' => 'Revisar'])

<button
    type="button"
    {{ $attributes->class(['badge', 'disabled:opacity-60', 'disabled:cursor-wait']) }}
    wire:click="{{ $action }}"
    wire:target="{{ $action }}"
    wire:loading.attr="disabled"
>
    <span wire:loading.remove wire:target="{{ $action }}">{{ $label }}</span>
    <span wire:loading.inline-flex wire:target="{{ $action }}" role="status" aria-live="polite" class="items-center gap-2" style="display: none;">
        <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.3" />
            <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
        Validando…
    </span>
</button>
