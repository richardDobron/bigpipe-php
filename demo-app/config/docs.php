<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Where the documentation is
    |--------------------------------------------------------------------------
    |
    | The markdown files of the documentation are those of the repository (docs/), which also build the
    | Docusaurus website. The first directory that exists is used: docs/ of the demo (the deploy copies the
    | files there), or docs/ of the repository next to it.
    |
    */

    'paths' => [
        base_path('docs'),
        base_path('../docs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | The sidebar
    |--------------------------------------------------------------------------
    |
    | The groups and the files in them (the same as website/sidebars.js), by the name of the file.
    |
    */

    'sidebar' => [
        'Getting started' => ['getting_started', 'how_it_works'],
        'API' => ['domops', 'pagelets', 'transport_markers', 'redirecting', 'dialogs', 'lazy_pagelets', 'bootloader', 'page_transitions', 'poller', 'payload'],
        'Examples' => ['example_page', 'example_forms', 'arbiter', 'example_configuration'],
        'Integrations' => ['react_integration', 'laravel_integration', 'laravel_recipes', 'symfony_integration', 'nette_integration', 'cakephp_integration', 'psr_integration'],
    ],

    'default' => 'getting_started',

    'edit_url' => 'https://github.com/richardDobron/bigpipe-php/edit/main/docs/',

];
