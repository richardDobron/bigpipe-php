<?php

namespace App\Http\Controllers;

use App\Docs\Docs;

class DocsController extends Controller
{
    public function __construct(private Docs $docs)
    {
    }

    public function index()
    {
        return redirect()->route('docs.show', config('docs.default'));
    }

    public function show(string $slug)
    {
        abort_unless($this->docs->exists($slug), 404);

        $page = $this->docs->page($slug);

        return $this->page('docs.show', $page + ['sidebar' => $this->docs->sidebar()], $page['heading'].' · Docs');
    }
}
