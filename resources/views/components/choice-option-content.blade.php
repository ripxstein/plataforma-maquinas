@props(['text' => '', 'imageUrl' => '', 'imageAlt' => '', 'number' => 1])

<span class="block min-w-0 flex-1 space-y-2">
    @if(filled($text))
        <span class="block break-words">{{ $text }}</span>
    @else
        <span class="sr-only">Opción {{ $number }}</span>
    @endif
    @if(filled($imageUrl))
        <img src="{{ $imageUrl }}" alt="{{ filled($imageAlt) ? $imageAlt : 'Imagen de la opción '.$number }}" class="block h-auto max-h-64 w-auto max-w-full rounded-lg bg-white object-contain" loading="lazy">
    @endif
</span>
