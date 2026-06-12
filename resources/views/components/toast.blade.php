<div
    x-data="{
        toasts: [],
        show(msg, type) {
            const id = Date.now();
            this.toasts.push({ id, msg, type: type || 'success' });
            setTimeout(() => this.remove(id), 3500);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
    }"
    @toast.window="show($event.detail.message, $event.detail.type)"
    class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">

    <template x-for="t in toasts" :key="t.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :class="t.type === 'error' ? 'bg-terracotta' : 'bg-salvia-dark'"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg shadow-lg text-paper text-sm font-medium pointer-events-auto">
            <span x-text="t.msg"></span>
            <button @click="remove(t.id)" class="opacity-50 hover:opacity-100 transition-opacity text-xs leading-none">✕</button>
        </div>
    </template>
</div>
