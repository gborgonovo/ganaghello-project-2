<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: Georgia, 'Times New Roman', serif; background: #EFEDE3; color: #2C322A; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 32px auto; background: #fff; border: 1px solid #E0DDD2; border-radius: 8px; overflow: hidden; }
  .head { background: #5C6B4F; color: #fff; padding: 24px 32px; }
  .head h1 { margin: 0; font-size: 20px; font-weight: normal; letter-spacing: 0.02em; }
  .head p  { margin: 4px 0 0; font-size: 13px; opacity: 0.75; font-family: Arial, sans-serif; }
  .body  { padding: 28px 32px; }
  .rule  { border: none; border-top: 1px solid #E0DDD2; margin: 24px 0; }
  .section-title { font-family: Arial, sans-serif; font-size: 10px; font-weight: bold; letter-spacing: 0.12em; text-transform: uppercase; color: #8A9E7A; margin: 0 0 14px; }
  .task-row { padding: 8px 0; border-bottom: 1px solid #EFEDE3; }
  .task-row:last-child { border-bottom: none; }
  .task-label { font-family: Arial, sans-serif; font-size: 11px; color: #8A9E7A; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px; }
  .task-label.late { color: #C98A6B; font-weight: bold; }
  .task-title { font-size: 15px; color: #2C322A; }
  .task-title a { color: #2C322A; text-decoration: none; }
  .task-title a:hover { text-decoration: underline; }
  .task-area { font-family: Arial, sans-serif; font-size: 12px; color: #8A9E7A; margin-top: 2px; }
  .dormant-row { padding: 7px 0; border-bottom: 1px solid #EFEDE3; display: flex; justify-content: space-between; align-items: baseline; }
  .dormant-row:last-child { border-bottom: none; }
  .dormant-title { font-size: 14px; }
  .dormant-title a { color: #2C322A; text-decoration: none; }
  .dormant-days { font-family: Arial, sans-serif; font-size: 11px; color: #C98A6B; white-space: nowrap; margin-left: 12px; }
  .footer { background: #EFEDE3; padding: 16px 32px; text-align: center; }
  .footer a { font-family: Arial, sans-serif; font-size: 12px; color: #5C6B4F; text-decoration: none; margin: 0 10px; }
  .footer a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="wrap">

  <div class="head">
    <h1>BiG-Log</h1>
    <p>Buongiorno {{ $userName }}.</p>
  </div>

  <div class="body">

    @php
      $hasTasks = collect($groups)->isNotEmpty();
    @endphp

    @if($hasTasks)
    <p class="section-title">Scadenze</p>

    @foreach($groups as $group)
      @foreach($group['tasks'] as $task)
      <div class="task-row">
        <div class="task-label{{ $group['late'] ? ' late' : '' }}">{{ $group['label'] }}</div>
        <div class="task-title">
          <a href="{{ url('/tasks/' . $task['id']) }}">{{ $task['title'] }}</a>
        </div>
        @if($task['area'])
        <div class="task-area">{{ $task['area'] }}</div>
        @endif
      </div>
      @endforeach
    @endforeach
    @endif

    @if(!empty($dormant))
    @if($hasTasks)<hr class="rule">@endif

    <p class="section-title">Fili che si raffreddano</p>

    @foreach($dormant as $node)
    <div class="dormant-row">
      <div class="dormant-title">
        @if(!empty($node['url']))
        <a href="{{ $node['url'] }}">{{ $node['label'] }}</a>
        @else
        {{ $node['label'] }}
        @endif
      </div>
      <div class="dormant-days">fermo da {{ $node['days_inactive'] }} giorni</div>
    </div>
    @endforeach
    @endif

  </div>

  <div class="footer">
    <a href="{{ url('/cruscotto') }}">Apri BiG-Log</a>
    <a href="{{ url('/impostazioni/notifiche') }}">Impostazioni notifiche</a>
  </div>

</div>
</body>
</html>
