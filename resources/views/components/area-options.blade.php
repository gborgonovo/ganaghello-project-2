{{-- Opzioni <option> delle aree, ordinate ad albero con indentazione.
     Va dentro una <select> (la prima <option> "Nessuna/Tutte" resta nella vista).
     Il valore selezionato lo gestisce wire:model della select. --}}
@foreach(\App\Models\Area::optionsTree() as $opt)
<option value="{{ $opt->id }}">{{ $opt->label }}</option>
@endforeach
