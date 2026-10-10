{{--
    The ghost of a pagelet that is on its way: placed right after its placeholder, it is shown while the placeholder is
    empty (see .ghost in app.css), so it goes away by itself when the pagelet arrives.
--}}
@props(['type'])

<div {{ $attributes->merge(['class' => 'ghost ghost-'.$type]) }} aria-hidden="true">
    @switch($type)
        @case('chart')
            <i class="bone" style="width:140px;height:30px"></i>
            <i class="bone" style="width:90px;height:10px;margin-top:10px"></i>
            <div class="ghost-bars">
                @foreach ([40, 60, 32, 80, 100, 72, 88] as $height)
                    <i class="bone" style="height:{{ $height }}%"></i>
                @endforeach
            </div>
            @break
        @case('list')
            @foreach ([78, 64, 86, 58, 70] as $width)
                <div class="ghost-row">
                    <i class="bone" style="width:{{ $width - 28 }}%;height:10px"></i>
                    <i class="bone" style="width:{{ $width }}%;height:8px"></i>
                </div>
            @endforeach
            @break
        @case('stats')
            <div class="ghost-stats">
                @foreach (range(1, 2) as $i)
                    <div><i class="bone" style="width:60px;height:10px"></i><i class="bone" style="width:44px;height:24px"></i></div>
                @endforeach
            </div>
            @break
    @endswitch
</div>
