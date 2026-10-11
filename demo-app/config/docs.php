<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The versions of the documentation
    |--------------------------------------------------------------------------
    |
    | The markdown files of the latest version are those of the repository (docs/), which also build the
    | Docusaurus website. The first directory that exists is used: docs/ of the demo (the deploy copies the
    | files there), or docs/ of the repository next to it. An older version is a copy of docs/ at its tag,
    | in resources/docs/<version>, and is edited on GitHub at that tag.
    |
    | The latest version is at /docs/<page>, an older one at /docs/<version>/<page>.
    |
    */

    'latest' => '2.1',

    'versions' => [
        '2.1' => [
            'paths' => [
                base_path('docs'),
                base_path('../docs'),
            ],
            'edit_url' => 'https://github.com/richardDobron/bigpipe-php/edit/main/docs/',
        ],
        '2.0' => [
            'paths' => [
                resource_path('docs/2.0'),
            ],
            'edit_url' => 'https://github.com/richardDobron/bigpipe-php/blob/2.0.0/docs/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The sidebar
    |--------------------------------------------------------------------------
    |
    | The groups and the files in them (the same as website/sidebars.js), by the name of the file. A version
    | shows the files it has.
    |
    */

    'sidebar' => [
        'Getting started' => ['getting_started', 'how_it_works'],
        'API' => ['domops', 'pagelets', 'transport_markers', 'redirecting', 'dialogs', 'lazy_pagelets', 'bootloader', 'page_transitions', 'poller', 'payload'],
        'Examples' => ['example_page', 'example_forms', 'arbiter', 'example_configuration'],
        'Integrations' => ['react_integration', 'laravel_integration', 'laravel_recipes', 'symfony_integration', 'nette_integration', 'cakephp_integration', 'psr_integration'],
    ],

    'default' => 'getting_started',

];
