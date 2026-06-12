@props(['href' => '#', 'active' => false, 'icon' => null])

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors
          {{ $active
              ? 'bg-salvia text-paper font-medium'
              : 'text-paper/70 hover:bg-salvia/40 hover:text-paper' }}">
    @if($icon)
        <x-icon :name="$icon" class="w-4 h-4 shrink-0" />
    @endif
    {{ $slot }}
</a>
