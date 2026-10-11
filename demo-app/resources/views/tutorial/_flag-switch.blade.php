<div class="segmented" id="flag-switch" aria-label="The variant of the flag">
    @foreach (['classic' => 'Classic', 'next' => 'Next', 'off' => 'Off'] as $key => $label)
        <a href="{{ url('/tutorial/feature-flags') }}" ajaxify="{{ route('feature-flags.change', $key) }}" rel="async-post"
           class="{{ $variant === $key ? 'on' : '' }}" @if ($variant === $key) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</div>
