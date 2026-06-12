@props(['href' => '#', 'active' => false])

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors
          {{ $active
              ? 'bg-salvia text-paper font-medium'
              : 'text-paper/70 hover:bg-salvia/40 hover:text-paper' }}">
    @isset($icon)
        <span class="w-4 text-center shrink-0 text-base leading-none">{{ $icon }}</span>
    @endisset
    {{ $slot }}
</a>
