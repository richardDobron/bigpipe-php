<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>BigPipe</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css2?family=Bitter:wght@200;300;400;500;600;700;800&family=Urbanist:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script src="{{ mix('/js/app.js') }}"></script>

    <style>
        body {
            font-family: urbanist,system-ui;
        }
    </style>
</head>
<body class="antialiased">
    <div class="relative flex items-top justify-center min-h-screen bg-gradient-to-br from-blue-500 via-blue-900 to-blue-700 sm:items-center py-4 sm:pt-0">
        <section class="mx-auto py-12 px-4 md:max-w-sm md:px-20 lg:max-w-7xl lg:rounded-lg lg:py-24 even">
            <div class="">
                <div class="grid grid-cols-12 items-center justify-center gap-8">
                    <div class="col-span-12 lg:col-span-6 lg:pr-20">
                        <a href="/" class="block mb-2"><img src="/images/logo.svg"></a>
                        <div class="text-sm uppercase tracking-wider text-primary-400"></div>
                        <div class="text-white flex gap-3 mt-4 mb-12 items-center">
                            <a href="/">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"></path>
                                </svg>
                            </a>
                            <h2 class="max-w-md text-3xl font-bold tracking-tight lg:text-5xl">@yield('header')</h2>
                        </div>
                        <div class="text-2xl text-slate-300 md:max-w-3xl">@yield('description')</div>
                        @yield('bullet-description')
                    </div>
                    <div class="relative col-span-12 lg:col-span-6">
                        <div class="">
                            <div class="w-full rounded-lg text-left text-sm text-slate-800 shadow-xl ring-1 ring-black ring-opacity-5">
                                <highlight inline-template>
                                   <pre class="text-base lg:text-lg line-numbers mx-4 lg:mx-0 hover:zoom"><code class="language-php">@yield('code')</code></pre>
                                </highlight>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @yield('examples')
    <div class="text-center">
        <a href="https://github.com/richardDobron/bigpipe-php" class="mb-2 inline-block" target="_blank"><span class="sr-only">Github</span>
            <div class="hover:opacity-80">
                <svg class="h-9 w-9" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"></path>
                </svg>
            </div>
        </a>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.27.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.27.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.27.0/plugins/autoloader/prism-autoloader.min.js"></script>

    <script>
        (new (require("ServerJS"))).handle(@json(\dobron\BigPipe\BigPipe::jsmods()));
    </script>
</body>
</html>
