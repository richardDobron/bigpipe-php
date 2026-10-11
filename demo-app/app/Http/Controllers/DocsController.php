<?php

namespace App\Http\Controllers;

use App\Docs\Docs;

class DocsController extends Controller
{
    public function index()
    {
        return redirect()->route('docs.show', config('docs.default'));
    }

    public function show(string $slug)
    {
        return $this->render(new Docs(), $slug);
    }

    /**
     * The first page of an older version.
     */
    public function versionIndex(string $version)
    {
        abort_unless(Docs::hasVersion($version), 404);

        return redirect((new Docs($version))->url(config('docs.default')));
    }

    /**
     * A page of an older version; the latest version is at /docs/<page>.
     */
    public function versionShow(string $version, string $slug)
    {
        abort_unless(Docs::hasVersion($version), 404);

        $docs = new Docs($version);

        if ($docs->isLatest()) {
            return redirect($docs->url($slug));
        }

        return $this->render($docs, $slug);
    }

    private function render(Docs $docs, string $slug)
    {
        abort_unless($docs->exists($slug), 404);

        $page = $docs->page($slug);

        // The same page in another version, or its first page when it does not have it.
        $urlIn = function (string $version) use ($slug): string {
            $other = new Docs($version);

            return $other->url($other->exists($slug) ? $slug : config('docs.default'));
        };

        return $this->page('docs.show', $page + [
            'sidebar' => $docs->sidebar(),
            'version' => $docs->version(),
            'latest' => Docs::latest(),
            'latestUrl' => $urlIn(Docs::latest()),
            'versions' => array_map(fn (string $version) => ['version' => $version, 'url' => $urlIn($version)], Docs::versions()),
        ], $page['heading'].' · Docs'.($docs->isLatest() ? '' : ' '.$docs->version()));
    }
}
