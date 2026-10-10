@props(['title', 'caption' => null])

<article {{ $attributes->merge(['class' => 'example']) }}>
    <div class="example-stage">
        <div class="example-demo">{{ $slot }}</div>
    </div>
    <div class="example-text">
        <h3>{{ $title }}</h3>
        @if ($caption)
            <p>{{ $caption }}</p>
        @endif
    </div>
</article>
