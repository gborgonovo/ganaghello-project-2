# migration_map.md

> Mappatura manuale delle categorie, stage e goal di v1 verso il nuovo schema v2.
> Da completare prima di eseguire `php artisan migrate:from-v1`.
> Ultimo aggiornamento: 2026-06-08

---

## Come usare questo file

- **Colonna "v2"**: scegli `area` o `tag` per ogni categoria, e indica il nome v2 esatto.
- **Area**: usa il nome di un'area gia' esistente nel seed (Abitazione, Stalla, Orto, Bosco, Accesso, Progetto B&B) oppure scrivine uno nuovo. Il comando creera' l'area se non esiste.
- **Tag**: usa il nome normalizzato in minuscolo. Se corrisponde a un tag gia' nel seed, verra' riusato.
- Tutto cio' che e' marcato `ignora` non viene migrato (ma i task che ci appartenevano continuano a esistere, semplicemente senza quella categoria).

---

## 1. Categorie v1 → Aree o Tag v2

Le categorie con piu' uso sono in cima. Le ultime 24 non hanno task assegnati.

| id v1 | Nome v1 | n. task | Proposta | v2: tipo | v2: nome | Note |
|-------|---------|---------|----------|----------|----------|------|
| 12 | Casa | 46 | area | `area` | `Abitazione` | |
| 1 | Giardino | 35 | area | `area` | `Giardino` | da creare (non e' nel seed) |
| 2 | Impianti | 29 | tag | `tag` | `impianti` | gia' nel seed |
| 4 | Struttura | 16 | tag | `tag` | `struttura` | gia' nel seed |
| 7 | Stalla | 13 | area | `area` | `Stalla` | gia' nel seed |
| 6 | Annessi | 12 | area | `area` | `Annessi` | da creare |
| 9 | Documenti | 8 | tag | `tag` | `documenti` | gia' nel seed |
| 8 | Silos | 5 | area | `area` | `Silos` | da creare |
| 3 | Comunicazione | 3 | tag | `tag` | `comunicazione` | da creare |
| 11 | Business Model | 3 | tag | `tag` | `business` | gia' nel seed come "business" |
| 13 | Laboratorio | 3 | area | `area` | `Magazzino` | da creare (sub-area di Stalletta) |
| 18 | Bagno Ovest | 3 | area | `area` | `Bagno Ovest` | da creare (sub-area di Abitazione) |
| 10 | Attrezzi | 2 | tag | `tag` | `attrezzi` | gia' nel seed |
| 14 | Camera da letto Ovest | 2 | area | `area` | `Camera da letto Ovest` | da creare (sub-area di Abitazione) |
| 17 | Cucina Ovest | 2 | area | `area` | `Cucina Ovest` | da creare (sub-area di Abitazione) |
| 19 | Stanza Blu | 2 | area | `area` | `Stanza Blu` | da creare (sub-area di Abitazione) |
| 15 | Soggiorno Ovest | 1 | area | `area` | `Soggiorno Ovest` | da creare (sub-area di Abitazione) |
| 27 | Stalletta | 1 | area | `area` | `Stalletta` | da creare |
| 35 | Prato Ovest | 1 | area | `area` | `Prato` | da creare (sub-area di Prato) |

### Categorie senza task (24) — proposta: ignora tutte, o crea le aree piu' importanti

Queste non hanno task e non vengono migrate automaticamente. Puoi comunque decidere di creare le aree vuote per completezza.

| id v1 | Nome v1 | Proposta |
|-------|---------|----------|
| 16 | Camera blu | `area` Camera blu (sub Abitazione) |
| 19 | Casa - Facciata nord | `area` Facciata Nord (sub Abitazione) |
| 20 | Casa - Facciata Sud | `area` Facciata Sud (sub Abitazione) |
| 21 | Casa - Facciata Est | `area` Facciata Est (sub Abitazione) |
| 22 | Casa - Facciata Ovest | `area` Facciata Ovest (sub Abitazione) |
| 23 | Casa - Cancello Ovest | `area` Cancello (sub Abitazione) |
| 24 | Annessi - Pozzo | `area` Pozzo (sub Annessi) |
| 25 | Annessi - Casetta | `area` Casetta (sub Annessi) |
| 26 | Annessi - Porcilaia | `area` Porcilaia (sub Annessi) |
| 28 | Giardino Centro | sub Giardino |
| 29 | Giardino - Porticato | sub Giardino |
| 30 | Giardino Est | sub Giardino |
| 31 | Giardino Ovest | sub Giardino |
| 32 | Giardino Sud | sub Giardino |
| 33 | Giardino Nord | sub Giardino |
| 34 | Prato Est | sub Prato |
| 36 | Prato Nord | sub Prato |
| 37 | Prato Sud | sub Prato |
| 38 | Prato Centro | sub Prato |

---

## 2. Stage v1 → Stage code v2

In v1 gli stage erano colori CSS senza significato formale. Il completamento reale era tracciato da `completed_on`.

**Regola automatica del comando:** se `completed_on IS NOT NULL` → stage `done` (indipendentemente dal colore).

Per i task non completati, la mappatura e' questa (da confermare):

| stage_id v1 | Colore v1 | n. task attivi | Proposta | v2 code | Conferma? |
|------------|-----------|---------------|----------|---------|-----------|
| 0 (null) | nessuno | 7 | task senza stage = idea/bozza | `idea` | [ ] |
| 1 | MediumVioletRed | 27 | progetti grandi in corso | `doing` | [ ] |
| 2 | DeepPink | 21 | da fare, priorita' normale | `todo` | [ ] |
| 3 | PaleVioletRed | 12 | approvati, da pianificare | `approvato` | [ ] |
| 4 | HotPink | 5 | task specifici piccoli | `todo` | [ ] |
| 5 | LightPink | 3 | idee/wishlist | `idea` | [ ] |

> **Nota**: stage 1 (MediumVioletRed) aveva task come "Acquistare il terreno accanto", "Alzare il soffitto", "Autorimessa" — sembrano grandi progetti in corso. Stage 5 (LightPink) aveva "Tagliare il fico sul vialetto" — piu' banali/rimandabili.

---

## 3. Goal v1 → Task L1 v2

**DECISIONE: i goal v1 NON vengono migrati.** I task v1 che avevano un `goal_id` vengono importati come task di primo livello (senza parent), ignorando il collegamento al goal.

| id v1 | Titolo v1 | Azione |
|-------|-----------|--------|
| 1 | Rendere abitabile l'unita' Ovest | ignorato |
| 5 | Compleanno Rossella | ignorato |

---

## 4. Items "note" e "mood" v1

| tipo v1 | n. | destinazione v2 | Note |
|---------|----|-----------------|------|
| note | 18 | `notes` | migrati con title + content |
| mood | 35 | `inspirations` | migrati solo se hanno title o cover; gli altri vengono loggati e saltati |

---

## 5. Riepilogo conteggi attesi post-migrazione

| Entita' v2 | Atteso | Note |
|-----------|--------|------|
| tasks | ~122 | tutti i type=task non deleted |
| notes | ~18 | tutti i type=note |
| inspirations | ~35 | solo quelli con title o cover |
| goals | — | non migrati (decisione) |
| media | da contare | file fisici da copiare da storage v1 |

---

## Da fare (Giorgio)

- [ ] Confermare/correggere la colonna "v2: nome" per ogni categoria
- [ ] Segnare quali categorie senza task vuoi creare come aree vuote
- [ ] Confermare la mappatura stage (colori → codici v2)
- [ ] Indicare la gerarchia esatta delle sotto-aree (chi e' figlio di chi)
