@php($orders = rand(50, 250))
@php($users = rand(50, 250))
@php($ordersDelta = rand(-30, 60))
@php($usersDelta = rand(-30, 60))
<div class="stats">
    <div class="stat">
        <div class="k">Orders</div>
        <div class="v">{{ $orders }}</div>
        <div class="d {{ $ordersDelta >= 0 ? 'up' : 'down' }}">{{ $ordersDelta >= 0 ? '+' : '' }}{{ $ordersDelta }}%</div>
    </div>
    <div class="stat">
        <div class="k">Users</div>
        <div class="v">{{ $users }}</div>
        <div class="d {{ $usersDelta >= 0 ? 'up' : 'down' }}">{{ $usersDelta >= 0 ? '+' : '' }}{{ $usersDelta }}%</div>
    </div>
</div>
