@php
$items = [
    ['route' => 'settings.profilo',   'label' => 'Profilo'],
    ['route' => 'settings.aree',      'label' => 'Aree'],
    ['route' => 'settings.stage',     'label' => 'Stage'],
    ['route' => 'settings.tag',       'label' => 'Tag'],
    ['route' => 'settings.utenti',    'label' => 'Utenti'],
    ['route' => 'settings.notifiche', 'label' => 'Notifiche'],
];
@endphp

{{-- Mobile: select a tendina --}}
<div class="sm:hidden mb-4">
    <select onchange="window.location.href = this.value"
            class="w-full text-sm border border-paper-dark rounded-lg px-3 py-2 bg-white focus:outline-none focus:border-salvia">
        @foreach($items as $item)
        <option value="{{ route($item['route']) }}"
                {{ request()->routeIs($item['route']) ? 'selected' : '' }}>
            {{ $item['label'] }}
        </option>
        @endforeach
    </select>
</div>

{{-- Desktop: nav laterale --}}
<nav class="hidden sm:block w-44 shrink-0 py-6 pr-6">
    <p class="text-[10px] font-semibold uppercase tracking-widest text-ink/30 px-3 mb-2">Impostazioni</p>
    <ul class="space-y-0.5">
        @foreach($items as $item)
        <li>
            <a href="{{ route($item['route']) }}"
               class="block px-3 py-1.5 rounded-lg text-sm transition-colors
                      {{ request()->routeIs($item['route'])
                         ? 'bg-salvia text-white font-medium'
                         : 'text-ink/60 hover:text-ink hover:bg-paper-dark' }}">
                {{ $item['label'] }}
            </a>
        </li>
        @endforeach
    </ul>
</nav>
