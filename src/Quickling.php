<?php

namespace dobron\BigPipe;

/**
 * Page transitions: the links of the site load the next page into the canvas, the element with the
 * content of the page, instead of loading it in full. The page turns them on with init(), and the
 * same controller answers a page transition with AsyncResponse::transition():
 *
 *     if (Quickling::isRequested()) {
 *         return (new AsyncResponse())->transition($content, 'Feed')->send();
 *     }
 */
class Quickling
{
    public const MODULE = 'bigpipe-util/dist/Quickling';

    /** The query parameter of a page transition request, with the version of the page. */
    public const PARAM = 'quickling';

    protected static ?string $version = null;
    protected static ?string $inactivePageRegex = null;
    /** @var list<string> */
    protected static array $badRequestKeys = [];
    protected static int $sessionLength = 0;

    /**
     * @param string|null $version the version of the pages, e.g. of the deployment: a page of another
     *                             version is loaded in full
     * @param string|null $inactivePageRegex the paths (with the query string) always loaded in full,
     *                                       as a JavaScript regular expression
     * @param list<string> $badRequestKeys query parameters that need a full page load
     * @param int $sessionLength after this many page transitions the next page is loaded in full,
     *                           0 for no limit
     */
    public static function configure(
        ?string $version = null,
        ?string $inactivePageRegex = null,
        array $badRequestKeys = [],
        int $sessionLength = 0
    ): void {
        static::$version = $version;
        static::$inactivePageRegex = $inactivePageRegex;
        static::$badRequestKeys = array_values($badRequestKeys);
        static::$sessionLength = $sessionLength;
    }

    /**
     * The version of the pages set with configure(), sent with every page transition.
     */
    public static function version(): ?string
    {
        return static::$version;
    }

    /**
     * Turns page transitions on for the page, with the element of the given id as the canvas. In a
     * page transition it does nothing: the page in the browser has them on already, so a layout that
     * calls it is rendered for a transition without sending the call with every response.
     *
     * @throws \Throwable
     */
    public static function init(string $canvasId): void
    {
        if (static::isRequested()) {
            return;
        }

        BigPipe::page()->call(static::MODULE, 'init', [
            TransportMarker::element($canvasId),
            array_filter([
                'version' => static::$version,
                'inactivePageRegex' => static::$inactivePageRegex,
                'badRequestKeys' => static::$badRequestKeys,
                'sessionLength' => static::$sessionLength,
            ]),
        ]);
    }

    /**
     * Whether the request is a page transition, to answer with AsyncResponse::transition().
     */
    public static function isRequested(): bool
    {
        return isset($_GET[static::PARAM]);
    }

    public static function reset(): void
    {
        static::configure();
    }
}
