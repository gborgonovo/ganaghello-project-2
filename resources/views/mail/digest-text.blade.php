BiG-Log
=======

Buongiorno {{ $userName }}.

@php
  $sectionLabels = [
    'scaduti'  => 'Scaduto',
    'oggi'     => 'Oggi',
    'domani'   => 'Domani',
    '3giorni'  => 'Entro 3 giorni',
    '7giorni'  => 'Entro 7 giorni',
    '30giorni' => 'Entro 30 giorni',
  ];
  $hasTasks = collect($sections)->flatten(1)->isNotEmpty();
@endphp
@if($hasTasks)
IN SCADENZA
-----------
@foreach($sectionLabels as $key => $label)
@if(!empty($sections[$key]))
{{ strtoupper($label) }}
@foreach($sections[$key] as $task)
· {{ $task['title'] }}{{ $task['area'] ? '  [' . $task['area'] . ']' : '' }}
  {{ url('/tasks/' . $task['id']) }}
@endforeach

@endif
@endforeach
@endif
@if(!empty($dormant))
FILI CHE SI RAFFREDDANO
-----------------------
@foreach($dormant as $node)
· {{ $node['label'] }}  (fermo da {{ $node['days_inactive'] }} giorni)
@if(!empty($node['url']))
  {{ $node['url'] }}
@endif
@endforeach

@endif
---
Apri BiG-Log: {{ url('/cruscotto') }}
Impostazioni: {{ url('/impostazioni/notifiche') }}
