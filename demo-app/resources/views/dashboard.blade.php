@extends('layouts.app')

@section('content')
    <x-page-head eyebrow="04 / Dashboard" title="Dashboard">The page is sent at once, then every part as soon as it is ready. The parts wait for slow “APIs” (0.6 s, 0.9 s and 1.2 s) at the same time, so the page takes about as long as the slowest.</x-page-head>

    <section class="grid md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="font-semibold mb-2">Revenue</h2>
            {!! new \App\Pagelets\RevenuePagelet() !!}
            <x-ghost type="chart" />
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="font-semibold mb-2">Latest comments</h2>
            {!! new \App\Pagelets\ActivityPagelet() !!}
            <x-ghost type="list" />
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 md:col-span-2">
            <h2 class="font-semibold mb-2">Report (loads when it is visible)</h2>
            {{-- A lazy pagelet takes its placeholder as content: the ghost is replaced with the pagelet. --}}
            {!! \App\Pagelets\ReportPagelet::lazy(route('dashboard.report'), placeholder: view('dashboard._report-ghost')->render()) !!}
        </div>
    </section>
@endsection
