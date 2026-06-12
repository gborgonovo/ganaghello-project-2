{{--
    Uso:  <x-date-input wire:model="due_date" class="..." />
    Uso con auto-save su TaskDetail:  <x-date-input wire:model="due_date" data-save="saveField" class="..." />

    Mostra gg/mm/aaaa all'utente; internamente passa yyyy-mm-dd a Livewire.
    Funziona indipendentemente dalla lingua del browser.
--}}

@props(['placeholder' => 'gg/mm/aaaa'])

@php
    $wireProp  = $attributes->whereStartsWith('wire:model')->first();
    $saveMethod = $attributes->get('data-save');
    $inputAttrs = $attributes->except(['wire:model', 'data-save']);
@endphp

<div
    x-data="{
        prop:    @js($wireProp),
        save:    @js($saveMethod),
        display: '',

        toDisplay(ymd) {
            if (!ymd) return '';
            const p = String(ymd).split('-');
            return p.length === 3 ? p[2]+'/'+p[1]+'/'+p[0] : '';
        },

        toYmd(dmy) {
            const p = String(dmy || '').replace(/\s/g, '').split('/');
            if (p.length !== 3) return '';
            const [d, m, y] = p;
            if (y.length < 4 || isNaN(+d) || isNaN(+m) || isNaN(+y)) return '';
            if (+m < 1 || +m > 12 || +d < 1 || +d > 31) return '';
            return y + '-' + m.padStart(2,'0') + '-' + d.padStart(2,'0');
        },

        sync() {
            const ymd = this.toYmd(this.display);
            this.$wire.set(this.prop, ymd || null);
            if (this.save) this.$wire[this.save](this.prop);
        },

        normalize() {
            this.display = this.toDisplay(this.toYmd(this.display));
        }
    }"
    x-init="display = toDisplay($wire[prop] || '')">

    <input
        type="text"
        inputmode="numeric"
        x-model="display"
        @change="sync()"
        @blur="normalize()"
        placeholder="{{ $placeholder }}"
        maxlength="10"
        {{ $inputAttrs }}>
</div>
