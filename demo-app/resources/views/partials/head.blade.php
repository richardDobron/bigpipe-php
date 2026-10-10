<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{{-- The theme before the first paint, so a dark page never flashes light: the choice of the switch, or the system. --}}
<script>
    (function () {
        var theme;
        try { theme = localStorage.getItem('theme'); } catch (e) {}
        document.documentElement.dataset.theme = theme === 'dark' || theme === 'light'
            ? theme
            : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    })();
</script>
<title>{{ $title ?? 'BigPipe' }}</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

{{-- The built CSS is a stylesheet of its own; on the dev server app.js injects it. --}}
@unless (\Illuminate\Support\Facades\Vite::isRunningHot())
    <link rel="stylesheet" href="{{ \Illuminate\Support\Facades\Vite::asset('style.css') }}">
@endunless
@vite('resources/js/app.js')
