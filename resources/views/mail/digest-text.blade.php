BiG-Log
=======

Buongiorno {{ $userName }}.

@php
  $hasTasks = collect($groups)->isNotEmpty();
@endphp
@if($hasTasks)
SCADENZE
--------
@foreach($groups as $group)
{{ strtoupper($group['label']) }}
@foreach($group['tasks'] as $task)
· {{ $task['title'] }}{{ $task['area'] ? '  [' . $task['area'] . ']' : '' }}
  {{ url('/tasks/' . $task['id']) }}
@endforeach

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
