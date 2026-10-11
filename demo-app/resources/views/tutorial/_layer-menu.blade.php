<div class="layer-menu" role="menu">
    @foreach ($items as $key => $label)
        <a href="{{ url('/tutorial/layers') }}" ajaxify="{{ route('layers.pick', $key) }}" rel="async-post" role="menuitem">{{ $label }}</a>
    @endforeach
</div>
