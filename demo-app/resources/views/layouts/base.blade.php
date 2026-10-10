{{--
    The one layout of the site: the top bar, the canvas and the footer. A page transition replaces only the
    canvas (#content) with the "canvas" section of the next page, so every page renders into it.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- The title: of the controller, of a "title" section, or the header of a tutorial. --}}
    @include('partials.head', ['title' => $title ?? html_entity_decode($__env->yieldContent('title', \App\Http\Controllers\Controller::titleOf($__env->yieldContent('header'))), ENT_QUOTES)])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{--
        The highlighter of the code, deferred: a blocking script before the scripts of BigPipe would hold up the
        pagelets behind it. tutorial/Code highlights the code again after a page transition.
    --}}
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.27.0/components/prism-core.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.27.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.27.0/plugins/autoloader/prism-autoloader.min.js"></script>
</head>
<body class="{{ $bodyClass }}">
    <div id="progress"></div>
    @include('partials.topbar')

    <main id="content">@yield('canvas')</main>

    <footer class="footer">
        <span class="label">BigPipe · MIT license</span>
        <span class="label"><a href="https://github.com/richardDobron/bigpipe-php">github.com/richardDobron/bigpipe-php</a></span>
    </footer>

    @php(\dobron\BigPipe\Quickling::init('content'))
    {{-- A streamed page leaves out the script and the closing tags: BigPipe::stream() sends the pagelets. --}}
    @unless ($partial ?? false)
        {!! \dobron\BigPipe\BigPipe::render() !!}
</body>
</html>
    @endunless
