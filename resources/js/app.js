import Sortable from 'sortablejs';

// Inizializza SortableJS su tutte le fasce del kanban.
// Chiamato sia al caricamento iniziale che dopo ogni re-render Livewire.
function initKanbanSortables() {
    document.querySelectorAll('[data-sortable-stage]').forEach(el => {
        if (el._sortable) {
            el._sortable.destroy();
        }
        el._sortable = new Sortable(el, {
            group:      'kanban-tasks',
            animation:  150,
            ghostClass: 'opacity-40',
            dragClass:  'shadow-lg',
            // Il resize della colonna CSS dopo il drop viene gestito da Livewire
            onEnd({ item, to, from }) {
                if (to === from) return;
                const taskId    = parseInt(item.dataset.taskId);
                const newStageId = parseInt(to.dataset.sortableStage);
                const wireEl    = to.closest('[wire\\:id]');
                if (wireEl && taskId && newStageId) {
                    Livewire.find(wireEl.getAttribute('wire:id'))?.moveTask(taskId, newStageId);
                }
            },
        });
    });
}

document.addEventListener('livewire:initialized', initKanbanSortables);
document.addEventListener('livewire:updated',     initKanbanSortables);
// Dopo una navigazione SPA (wire:navigate) 'initialized' non riscatta:
// reinizializza i sortable del kanban a ogni cambio pagina.
document.addEventListener('livewire:navigated',    initKanbanSortables);
