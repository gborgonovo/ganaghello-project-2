# BiG-Log — Avanzamento

> Memoria di sessione in sessione. Aggiornare all'inizio e alla fine di ogni sessione di lavoro.
> Ultimo aggiornamento: 2026-06-08

---

## Stato attuale

### Completato
- [x] Progettazione completa (commit 369b65c)
  - Concept, modello dati, stack, 8 viste UI
  - CRUD task e entita' di contorno
  - Trasversali: auth, media/documenti, settings, digest email, ricerca, bookmarklet, seed
  - Ponte al build: sequenza migrazioni, piano migrazione da v1, design system
  - Delta handoff: 5 rifiniture UI
- [x] Git inizializzato (branch: main)

### In corso
- Nulla: la fase di progettazione e' chiusa. Si parte con il build.

---

## Task corrente
**Nessuno** — in attesa di iniziare la Fase 0 del build.

---

## Prossimi task in ordine

Seguono le fasi di `documentation/design/06-ponte-al-build.md`:

1. **Fase 0: setup progetto**
   - `laravel new biglog` nella cartella corrente (o installazione in `/srv/http/ganaghello-project-2`)
   - Laravel Breeze (auth email+password)
   - Livewire 3 + Alpine + Tailwind
   - Livewire Flux
   - SQLite in `.env`
   - Layout base (navbar, sidebar, routing)

2. **Fase 0.5: migrazione dati da v1**
   - Preparazione manuale: classificare categorie v1 → aree o tag (in `migration_map.md`)
   - Classificare goal v1 → task L1
   - Scrivere `artisan migrate:from-v1`
   - Scrivere `artisan media:regenerate-thumbs`

3. **Fase 1: migration + seed + modelli Eloquent**
   - Tutte le migration nell'ordine del documento
   - Seeder (User, Stage, Area, Tag, Settings)
   - Tutti i Model con relazioni prima di toccare le viste

4. **Fase 2: cruscotto**

5. **Fase 3: task CRUD**

6. **Fase 4: kanban**

_(le fasi successive sono in `06-ponte-al-build.md`)_

---

## Decisioni prese

### Stack
- Laravel 11 + SQLite + Livewire 3 + Alpine + Tailwind
- Livewire Flux per componenti UI base
- Intervention Image 3 per processing immagini (3 versioni: thumb 400px, medium 1400px, original max 3000px)
- SortableJS per drag & drop (kanban, ordinamento immagini)
- Frappe Gantt (o custom) per la timeline
- Laravel Breeze per auth
- Laravel Queue driver: database (stesso SQLite)

### Architettura
- Monoutente per ora; modello multiutente gia' pronto (user_id su tutto, campo `role`)
- Stage identificati da `code` immutabile (non da `label`); la logica usa sempre `stage->code`
- Tag polimorfici: pivot `taggables` (taggable_type + taggable_id)
- Immagini in storage privato, servite da route autenticata `/media/{media}/{size}`
- Badge stage e area: stili inline (`bg_color` + `text_color` dal DB), non classi Tailwind
- Sync Mnemosyne: sempre asincrona via Laravel Queue, mai bloccante
- Blog pubblico: Blade SSR, route `/blog/*` senza middleware auth

### Convenzioni
- `stage->code` per confronti logici, mai `stage->label`
- Tag: salvati in minuscolo, mostrati ucfirst, case-insensitive alla creazione
- `cost_real` su task e somma `expenses`: coesistono, non si sovrascrivono
- Notifiche email: per-utente (ogni utente riceve solo i task che lo riguardano); trigger su soglie esatte (non ogni giorno)
- `notify_periods`: stringa CSV es. `"0,1,3,7"` in `settings`
- Migrazione v1: comando `artisan migrate:from-v1`, seconda connessione SQLite `db_v1`

### Palette "Calce & salvia"
- Paper: `#EFEDE3` (sfondo)
- Ink: `#2C322A` (testo)
- Salvia: `#5C6B4F` (primario)
- Terracotta: `#C98A6B` (accento)

### Tipografia
- Operativo (UI, form, kanban): Inter / system-ui
- Narrativo (diario, blog): Lora / Georgia

---

## Problemi aperti

- **Mnemosyne modifica #5**: `/tasks` emette `LINKED_TO` invece di `PART_OF` verso il padre. Da risolvere lato `MnemosyneService` (usare `/nodes` con `relations` esplicite). Rimandato al momento di scrivere il service.
- **Blog visual design**: immagini orizzontali full-width non convincono Giorgio. Da affrontare durante il build quando c'e' qualcosa da vedere.
- **MnemosyneService mechanics**: dettaglio implementativo di come emettere `PART_OF`, `CONTRIBUTES_TO`, `LOCATED_IN` e cosa fare al completamento di un task. Da progettare durante la Fase 14.
- **Aree reali e gerarchia**: il seed ha le aree principali ma la gerarchia esatta (sotto-aree di Abitazione ecc.) va completata da Giorgio prima della Fase 0.5.
- **migration_map.md**: classificazione manuale delle categorie v1 → aree o tag. Da fare prima della Fase 0.5.
