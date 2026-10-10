@props(['eyebrow', 'title'])

<div class="page-head">
    <div class="eyebrow"><span class="label">{{ $eyebrow }}</span><span class="bar"></span></div>
    <h1 class="hero-title" style="font-size:clamp(32px,4.4vw,48px)">{{ $title }}</h1>
    <p class="lead" style="margin-top:12px">{{ $slot }}</p>
</div>
