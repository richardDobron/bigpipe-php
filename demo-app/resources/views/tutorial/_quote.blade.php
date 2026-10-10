<form id="quote" class="quote" onsubmit="return false" autocomplete="off">
    <div class="field">
        <label>Plan</label>
        <div class="segmented">
            @foreach (['starter' => 'Starter · $6', 'team' => 'Team · $12'] as $value => $label)
                <label class="{{ $plan === $value ? 'on' : '' }}"><input type="radio" name="plan" value="{{ $value }}" @checked($plan === $value)> {{ $label }}</label>
            @endforeach
        </div>
    </div>
    <div class="field">
        <label for="seats">Seats</label>
        <input type="text" inputmode="numeric" name="seats" id="seats" value="{{ $seats }}">
        @if ($error)
            <p class="error">{{ $error }}</p>
        @endif
    </div>
    <div class="field">
        <label for="coupon">Coupon <span class="label">try PIPE20</span></label>
        <input type="text" name="coupon" id="coupon" value="{{ $coupon }}" placeholder="Optional">
    </div>
    <div class="quote-total">
        <span>Per month</span>
        <strong>{{ $total === null ? '—' : '$'.number_format($total, 2) }}</strong>
    </div>
    <input type="hidden" name="mode" value="{{ $mode }}">
</form>
