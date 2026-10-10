<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Quickling;
use Illuminate\Contracts\View\View;

abstract class Controller
{
    /**
     * A page of the site: only its canvas for a page transition, the whole page otherwise. Without a
     * title, the title is the header of the page (the tutorials).
     */
    protected function page(string $view, array $data = [], ?string $title = null)
    {
        $transition = Quickling::isRequested();

        // A partial layout leaves out the script of BigPipe: it would take the pagelets and modules
        // of the content before they are in the response.
        $page = view($view, $data + ['partial' => $transition] + ($title === null ? [] : ['title' => self::titleOf($title)]));

        return $transition ? $this->transition($page, $title)->send() : $page;
    }

    /**
     * A page whose pagelets are streamed as they are rendered: the layout first, then every pagelet. A page
     * transition streams them too, when the browser asks for a streamed response.
     */
    protected function streamedPage(string $view, array $data = [], ?string $title = null)
    {
        if (Quickling::isRequested()) {
            $response = $this->transition(view($view, $data + ['partial' => true]), $title);

            return AsyncResponse::isStreamRequested()
                ? response()->stream(fn () => $response->stream(), 200, AsyncResponse::headers() + ['X-Accel-Buffering' => 'no'])
                : $response->send();
        }

        return response()->stream(function () use ($view, $data, $title) {
            echo view($view, $data + ['partial' => true] + ($title === null ? [] : ['title' => self::titleOf($title)]))->render();
            BigPipe::stream();
            echo '</body></html>';
        }, 200, ['Content-Type' => 'text/html; charset=utf-8', 'X-Accel-Buffering' => 'no']);
    }

    public static function titleOf(?string $name): string
    {
        $name = trim(html_entity_decode((string) $name, ENT_QUOTES));

        return $name === '' ? 'BigPipe' : $name.' · BigPipe';
    }

    /**
     * The canvas of a page for a page transition. The layout, the scripts and everything the page has
     * loaded stay.
     */
    private function transition(View $page, ?string $title): AsyncResponse
    {
        $sections = $page->renderSections();

        return (new AsyncResponse())->transition(
            $sections['canvas'],
            $title === null ? html_entity_decode($sections['title'] ?? self::titleOf($sections['header'] ?? null), ENT_QUOTES) : self::titleOf($title),
            view()->shared('bodyClass', '')
        );
    }
}
