<?php

namespace App\Livewire;

use App\Actions\AggiornamentoActions;
use App\Actions\SpesaActions;
use App\Actions\TagActions;
use App\Actions\TaskActions;
use App\Models\Area;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskDetail extends Component
{
    public Task $task;

    // Campi editabili in-place
    public string  $title        = '';
    public string  $description  = '';
    public ?int    $stage_id     = null;
    public ?int    $area_id      = null;
    public ?int    $parent_task_id = null;
    public string  $priority     = '';
    public string  $due_date     = '';
    public string  $start_date   = '';
    public string  $executor     = '';
    public string  $work_estimate  = '';
    public string  $cost_min      = '';
    public string  $cost_max      = '';
    public string  $cost_basis    = '';
    public string  $cost_real     = '';
    public string  $completed_at  = '';
    public array   $tagIds       = [];
    public array   $goalIds      = [];
    public ?int    $assigned_to  = null;
    public array   $collaboratorIds = [];

    // Spese
    public bool   $showExpenseForm  = false;
    public ?int   $editingExpenseId = null;
    public string $expDescription   = '';
    public string $expAmount        = '';
    public string $expDate          = '';
    public string $expCategory      = '';
    public string $expSupplier      = '';
    public string $expNotes         = '';

    // UI state
    public bool   $editingTitle       = false;
    public bool   $editingDescription = false;
    public bool   $confirmDelete      = false;
    public ?int   $editingUpdateId    = null;
    public string $editingUpdateContent = '';

    // Stage
    public bool   $showDueDatePrompt     = false;
    public bool   $showCompletedAtPrompt = false;
    public string $pendingCompletedAt    = '';
    public ?int   $pendingStageId        = null;

    // Sotto-task
    public string $newSubtaskTitle = '';

    // Task updates
    public string $newUpdateContent = '';

    // Collegamento con le voci di diario (pivot task_entry)
    public bool   $showEntryPicker = false;
    public string $entrySearch     = '';

    // Tag
    public string $tagInput = '';

    // Navigazione: URL da cui si è arrivati (per il redirect dopo eliminazione)
    public string $backUrl = '';

    public function mount(Task $task): void
    {
        $this->task    = $task->load(['stage', 'area', 'tags', 'goals', 'updates', 'children.stage', 'parent', 'assignedUser', 'collaborators', 'entries.attachments.media', 'expenses' => fn ($q) => $q->orderByDesc('expense_date')->orderByDesc('id')]);
        $this->syncFromModel();
        $this->backUrl = $this->resolveBackUrl();
    }

    private function resolveBackUrl(): string
    {
        $previous = url()->previous();
        $current  = url()->current();

        if ($previous
            && $previous !== $current
            && str_starts_with($previous, config('app.url'))) {
            return $previous;
        }

        return route('cruscotto');
    }

    private function syncFromModel(): void
    {
        $this->title         = $this->task->title;
        $this->description   = $this->task->description ?? '';
        $this->stage_id      = $this->task->stage_id;
        $this->area_id       = $this->task->area_id;
        $this->parent_task_id = $this->task->parent_task_id;
        $this->priority      = $this->task->priority ?? '';
        $this->due_date      = $this->task->due_date?->format('Y-m-d') ?? '';
        $this->start_date    = $this->task->start_date?->format('Y-m-d') ?? '';
        $this->executor      = $this->task->executor ?? '';
        $this->work_estimate = $this->task->work_estimate !== null ? (string) $this->task->work_estimate : '';
        $this->cost_min      = $this->task->cost_min !== null ? (string) $this->task->cost_min : '';
        $this->cost_max      = $this->task->cost_max !== null ? (string) $this->task->cost_max : '';
        $this->cost_basis    = $this->task->cost_basis ?? '';
        $this->cost_real     = $this->task->cost_real !== null ? (string) $this->task->cost_real : '';
        $this->completed_at  = $this->task->completed_at?->format('Y-m-d') ?? '';
        $this->tagIds        = $this->task->tags->pluck('id')->toArray();
        $this->goalIds       = $this->task->goals->pluck('id')->toArray();
        $this->assigned_to   = $this->task->assigned_to;
        $this->collaboratorIds = $this->task->collaborators->pluck('id')->toArray();
    }

    // Stage selector
    public function setStage(int $stageId): void
    {
        $stage = Stage::find($stageId);
        $closedCodes = ['done', 'archiviato'];

        // Stage aperti che richiedono una scadenza
        if (in_array($stage->code, ['todo', 'doing', 'in_attesa']) && !$this->task->due_date && !$this->due_date) {
            $this->pendingStageId    = $stageId;
            $this->showDueDatePrompt = true;
            return;
        }

        // Stage chiusi: chiedi la data di completamento (solo se non gia' impostata)
        if (in_array($stage->code, $closedCodes) && !$this->task->completed_at) {
            $this->pendingStageId        = $stageId;
            $this->pendingCompletedAt    = now('Europe/Rome')->format('Y-m-d');
            $this->showCompletedAtPrompt = true;
            return;
        }

        $this->applyStage($stageId);
    }

    public function confirmDueDate(): void
    {
        if (!$this->due_date) return;
        $this->applyStage($this->pendingStageId);
        $this->showDueDatePrompt = false;
        $this->pendingStageId    = null;
    }

    public function cancelDueDate(): void
    {
        $this->showDueDatePrompt = false;
        $this->pendingStageId    = null;
    }

    public function confirmCompletedAt(): void
    {
        $this->applyStage($this->pendingStageId);
        $this->showCompletedAtPrompt = false;
        $this->pendingStageId        = null;
    }

    public function cancelCompletedAt(): void
    {
        $this->showCompletedAtPrompt = false;
        $this->pendingStageId        = null;
        $this->pendingCompletedAt    = '';
    }

    private function applyStage(int $stageId): void
    {
        $stage = Stage::find($stageId);

        app(TaskActions::class)->spostaStage(Auth::user(), $this->task, $stage->code,
            scadenza: $this->due_date ?: null,
            chiusoIl: $this->task->completed_at ? null : ($this->pendingCompletedAt ?: null));

        $this->pendingCompletedAt = '';
        $this->task->refresh()->load(['stage', 'area', 'tags', 'goals', 'updates', 'children.stage', 'parent']);
        $this->syncFromModel();
        $this->dispatch('toast', message: 'Stato aggiornato.');
    }

    // Campo title
    public function saveTitle(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        app(TaskActions::class)->modifica(Auth::user(), $this->task, ['title' => $this->title]);
        $this->editingTitle = false;
        $this->task->refresh();
    }

    // Campo description
    public function saveDescription(): void
    {
        app(TaskActions::class)->modifica(Auth::user(), $this->task, ['description' => $this->description]);
        $this->editingDescription = false;
    }

    // Salvataggio generico dei campi singoli su blur
    public function saveField(string $field): void
    {
        $allowed = ['area_id', 'priority', 'due_date', 'start_date', 'executor',
                    'work_estimate', 'cost_min', 'cost_max', 'cost_basis', 'cost_real',
                    'completed_at', 'parent_task_id'];
        if (!in_array($field, $allowed)) return;

        app(TaskActions::class)->modifica(Auth::user(), $this->task, [$field => $this->$field]);
        $this->task->refresh()->load(['stage', 'area', 'tags', 'goals', 'updates', 'children.stage']);
        $this->syncFromModel();
    }

    // Tag
    public function addTag(): void
    {
        if (!trim($this->tagInput)) return;
        app(TagActions::class)->applica(Auth::user(), $this->task, [$this->tagInput]);
        $this->task->refresh()->load('tags');
        $this->tagIds   = $this->task->tags->pluck('id')->toArray();
        $this->tagInput = '';
    }

    public function removeTag(int $id): void
    {
        app(TagActions::class)->applica(Auth::user(), $this->task, togli: [$id]);
        $this->task->refresh()->load('tags');
        $this->tagIds = $this->task->tags->pluck('id')->toArray();
    }

    // Goal
    public function toggleGoal(int $goalId): void
    {
        $collegato = in_array($goalId, $this->goalIds);
        app(TaskActions::class)->collegaGoal(Auth::user(), $this->task,
            aggiungi: $collegato ? [] : [$goalId],
            togli: $collegato ? [$goalId] : []);
        $this->task->refresh()->load('goals');
        $this->goalIds = $this->task->goals->pluck('id')->toArray();
    }

    // Sotto-task
    public function addSubtask(): void
    {
        $title = trim($this->newSubtaskTitle);
        if (!$title) return;

        app(TaskActions::class)->crea(Auth::user(), ['title' => $title, 'parent_task_id' => $this->task->id]);

        $this->newSubtaskTitle = '';
        $this->task->refresh()->load('children.stage');
        $this->dispatch('toast', message: 'Sotto-task creato.');
    }

    // ===== Voci di diario collegate (pivot task_entry) =====

    /** Aggancia una voce di diario gia' scritta a questo task. */
    public function linkEntry(int $entryId): void
    {
        app(TaskActions::class)->collegaVoci(Auth::user(), $this->task, aggiungi: [$entryId]);
        $this->task->refresh()->load('entries.attachments.media');
        $this->showEntryPicker = false;
        $this->entrySearch     = '';
        $this->dispatch('toast', message: 'Voce di diario collegata.');
    }

    public function unlinkEntry(int $entryId): void
    {
        app(TaskActions::class)->collegaVoci(Auth::user(), $this->task, togli: [$entryId]);
        $this->task->refresh()->load('entries.attachments.media');
        $this->dispatch('toast', message: 'Collegamento rimosso.');
    }

    public function toggleEntryPicker(): void
    {
        $this->showEntryPicker = !$this->showEntryPicker;
        $this->entrySearch     = '';
    }

    // Task updates
    public function addUpdate(): void
    {
        $content = trim($this->newUpdateContent);
        if (!$content) return;

        app(AggiornamentoActions::class)->crea(Auth::user(), $this->task, $content);

        $this->newUpdateContent = '';
        $this->task->refresh()->load('updates');
        $this->dispatch('toast', message: 'Aggiornamento aggiunto.');
    }

    public function startEditUpdate(int $id): void
    {
        $update = $this->task->updates->firstWhere('id', $id);
        if ($update) {
            $this->editingUpdateId      = $id;
            $this->editingUpdateContent = $update->content;
        }
    }

    public function saveUpdate(): void
    {
        $content = trim($this->editingUpdateContent);
        if (!$content) return;

        app(AggiornamentoActions::class)->modifica(Auth::user(), $this->task->updates()->findOrFail($this->editingUpdateId), $content);

        $this->editingUpdateId      = null;
        $this->editingUpdateContent = '';
        $this->task->refresh()->load('updates');
        $this->dispatch('toast', message: 'Aggiornamento modificato.');
    }

    public function cancelEditUpdate(): void
    {
        $this->editingUpdateId      = null;
        $this->editingUpdateContent = '';
    }

    public function deleteUpdate(int $id): void
    {
        app(AggiornamentoActions::class)->elimina(Auth::user(), $this->task->updates()->findOrFail($id));
        $this->task->refresh()->load('updates');
        $this->dispatch('toast', message: 'Aggiornamento eliminato.');
    }

    // Eliminazione task
    public function deleteTask(): void
    {
        app(TaskActions::class)->elimina(Auth::user(), $this->task, sottotask: 'lascia');
        $this->redirect($this->backUrl, navigate: true);
    }

    // Assegnazione
    public function saveAssignee(): void
    {
        app(TaskActions::class)->assegna(Auth::user(), $this->task, $this->assigned_to ?: null);
        $this->task->load('assignedUser');
        $this->dispatch('toast', message: 'Responsabile aggiornato.');
    }

    public function toggleCollaborator(int $userId): void
    {
        if (in_array($userId, $this->collaboratorIds)) {
            $this->collaboratorIds = array_values(array_diff($this->collaboratorIds, [$userId]));
        } else {
            $this->collaboratorIds[] = $userId;
        }
        app(TaskActions::class)->assegna(Auth::user(), $this->task, null, $this->collaboratorIds, cambiaResponsabile: false);
        $this->task->load('collaborators');
        $this->dispatch('toast', message: 'Collaboratori aggiornati.');
    }

    // Spese (inserimento manuale, niente upload ricevuta)
    public function newExpense(): void
    {
        $this->resetExpenseForm();
        $this->expDate         = today('Europe/Rome')->toDateString();
        $this->showExpenseForm = true;
    }

    public function editExpense(int $id): void
    {
        $e = $this->task->expenses()->findOrFail($id);
        $this->editingExpenseId = $id;
        $this->expDescription   = $e->description;
        $this->expAmount        = (string) $e->amount;
        $this->expDate          = $e->expense_date?->toDateString() ?? '';
        $this->expCategory      = $e->category ?? '';
        $this->expSupplier      = $e->supplier ?? '';
        $this->expNotes         = $e->notes ?? '';
        $this->showExpenseForm  = true;
    }

    public function saveExpense(): void
    {
        $this->validate([
            'expDescription' => 'required|string|max:255',
            'expAmount'      => 'required|numeric|min:0.01',
            'expDate'        => 'nullable|date',
            'expCategory'    => 'nullable|in:materiali,manodopera,professionisti,oneri,altro',
            'expSupplier'    => 'nullable|string|max:255',
            'expNotes'       => 'nullable|string',
        ]);

        $data = [
            'description'  => trim($this->expDescription),
            'amount'       => (float) $this->expAmount,
            'category'     => $this->expCategory ?: null,
            'supplier'     => trim($this->expSupplier) ?: null,
            'expense_date' => $this->expDate ?: null,
            'notes'        => trim($this->expNotes) ?: null,
        ];

        if ($this->editingExpenseId) {
            app(SpesaActions::class)->modifica(Auth::user(), $this->task->expenses()->findOrFail($this->editingExpenseId), $data);
            $msg = 'Spesa aggiornata.';
        } else {
            app(SpesaActions::class)->crea(Auth::user(), $this->task, $data);
            $msg = 'Spesa aggiunta.';
        }

        $this->task->load(['expenses' => fn ($q) => $q->orderByDesc('expense_date')->orderByDesc('id')]);
        $this->resetExpenseForm();
        $this->dispatch('toast', message: $msg);
    }

    public function cancelExpense(): void
    {
        $this->resetExpenseForm();
    }

    public function deleteExpense(int $id): void
    {
        app(SpesaActions::class)->elimina(Auth::user(), $this->task->expenses()->findOrFail($id));
        $this->task->load(['expenses' => fn ($q) => $q->orderByDesc('expense_date')->orderByDesc('id')]);
        $this->dispatch('toast', message: 'Spesa eliminata.');
    }

    private function resetExpenseForm(): void
    {
        $this->showExpenseForm  = false;
        $this->editingExpenseId = null;
        $this->expDescription   = '';
        $this->expAmount        = '';
        $this->expDate          = '';
        $this->expCategory      = '';
        $this->expSupplier      = '';
        $this->expNotes         = '';
        $this->resetValidation();
    }

    public function render()
    {
        // Voci di diario agganciabili: cerca per titolo/testo, escluse quelle gia' collegate.
        $entryCandidates = $this->showEntryPicker
            ? Entry::whereNotIn('id', $this->task->entries->pluck('id'))
                ->when($this->entrySearch, fn ($q) => $q->where(fn ($qq) => $qq
                    ->where('title', 'like', '%' . $this->entrySearch . '%')
                    ->orWhere('content', 'like', '%' . $this->entrySearch . '%')))
                ->orderByDesc('entry_date')->limit(25)->get()
            : collect();

        return view('livewire.task-detail', [
            'entryCandidates' => $entryCandidates,
            'stages'       => Stage::orderBy('sequence')->get(),
            'areas'        => Area::orderBy('sequence')->get(),
            'goals'        => Goal::where('status', 'active')->orderBy('title')->get(),
            'selectedTags' => Tag::whereIn('id', $this->tagIds)->get(),
            'users'        => User::orderBy('name')->get(),
            'suppliers'    => Expense::whereNotNull('supplier')->distinct()->orderBy('supplier')->pluck('supplier'),
        ])->layout('layouts.app', ['title' => $this->task->title]);
    }
}
