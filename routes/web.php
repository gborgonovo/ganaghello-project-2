<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PublicBlogController;
use App\Livewire\AreaDetail;
use App\Livewire\BlogIndex;
use App\Livewire\PostEditor;
use App\Livewire\MediaLibrary;
use App\Livewire\InspirationShow;
use App\Livewire\MoodboardIndex;
use App\Livewire\NoteIndex;
use App\Livewire\NoteShow;
use App\Livewire\AreaIndex;
use App\Livewire\Cruscotto;
use App\Livewire\DiarioIndex;
use App\Livewire\DiarioShow;
use App\Livewire\GoalList;
use App\Livewire\TimelineIndex;
use App\Livewire\Kanban;
use App\Livewire\TaskDetail;
use App\Livewire\Settings\Profilo;
use App\Livewire\Settings\Aree as SettingsAree;
use App\Livewire\Settings\StageSettings;
use App\Livewire\Settings\TagSettings;
use App\Livewire\Settings\Utenti;
use Illuminate\Support\Facades\Route;

// ===== VETRINA PUBBLICA (Blade SSR, no auth) — è la home del sito =====
// Il pubblico atterra sul blog; l'admin loggato ha il varco al cruscotto in testata.
// L'ordine conta: img e' dichiarata prima di {slug} per non essere catturata come slug.
Route::get('/', [PublicBlogController::class, 'index'])->name('storie.index');
Route::get('/storie', fn () => redirect('/', 301)); // vecchio percorso → home
Route::get('/storie/img/{media}/{size?}', [PublicBlogController::class, 'image'])->name('storie.img');
Route::get('/storie/{slug}', [PublicBlogController::class, 'show'])->name('storie.show');

// Viste gestionale (protette da auth)
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/cruscotto', Cruscotto::class)->name('cruscotto');

    Route::get('/kanban', Kanban::class)->name('kanban');

    Route::get('/timeline', TimelineIndex::class)->name('timeline');

    Route::get('/diario', DiarioIndex::class)->name('diario');
    Route::get('/diario/{entry}', DiarioShow::class)->name('diario.show');

    // Blog: gestione (Livewire, dietro auth). La vetrina pubblica e' su /storie.
    Route::get('/blog', BlogIndex::class)->name('blog');
    Route::get('/blog/nuovo', PostEditor::class)->name('blog.new');
    Route::get('/blog/{post}/modifica', PostEditor::class)->name('blog.edit');

    Route::get('/goals', GoalList::class)->name('goals');

    Route::get('/aree', AreaIndex::class)->name('aree');
    Route::get('/aree/{area}', AreaDetail::class)->name('aree.show');

    Route::prefix('impostazioni')->name('settings.')->group(function () {
        Route::get('/', fn () => redirect()->route('settings.profilo'))->name('index');
        Route::get('/profilo', Profilo::class)->name('profilo');
        Route::get('/aree',    SettingsAree::class)->name('aree');
        Route::get('/stage',   StageSettings::class)->name('stage');
        Route::get('/tag',     TagSettings::class)->name('tag');
        Route::get('/utenti',    Utenti::class)->name('utenti');
        Route::get('/notifiche', \App\Livewire\Settings\NotificheSettings::class)->name('notifiche');
        Route::get('/log',       \App\Livewire\Settings\Log::class)->name('log');
    });

    Route::get('/tasks/{task}', TaskDetail::class)->name('tasks.show');

    Route::get('/libreria', MediaLibrary::class)->name('libreria');
    Route::get('/moodboard', MoodboardIndex::class)->name('moodboard');
    Route::get('/moodboard/{inspiration}', InspirationShow::class)->name('moodboard.show');
    Route::get('/note', NoteIndex::class)->name('note');
    Route::get('/note/{note}', NoteShow::class)->name('note.show');

    Route::get('/media/{media}/{size?}', [MediaController::class, 'serve'])->name('media.serve');

    // Profilo (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
