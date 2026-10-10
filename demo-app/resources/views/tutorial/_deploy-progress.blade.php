@php($steps = ['Build the assets', 'Run the tests', 'Migrate the database', 'Switch the traffic'])
@php($done = (int) floor($progress / 100 * count($steps)))
<div class="meter {{ $progress >= 100 ? 'is-done' : '' }}"><span style="width:{{ $progress }}%"></span></div>
<div class="meter-label"><span>{{ $progress >= 100 ? 'Done' : 'In progress' }}</span><span>{{ $progress }}%</span></div>
<ul class="checklist">
    @foreach ($steps as $i => $step)
        <li class="{{ $i < $done ? 'is-done' : ($i === $done ? 'is-running' : '') }}">{{ $step }}</li>
    @endforeach
</ul>
