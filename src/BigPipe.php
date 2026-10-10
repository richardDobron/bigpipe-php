<?php

namespace dobron\BigPipe;

class BigPipe
{
    use JsMods;

    public const CSRF_TOKEN_MODULE = 'CSRFToken';

    public const CSP_NONCE_MODULE = 'CSPNonce';

    /** Marks the end of a streamed page: the browser starts loading the JS of the pagelets. */
    public const LAST_PAGELET_ID = '__bigpipe_last';

    protected static ?Context $defaultContext = null;

    /** @var null|callable(): Context */
    protected static $contextResolver = null;

    protected Context $context;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?? static::context();
    }

    /**
     * Returns the context of the current request.
     */
    public static function context(): Context
    {
        if (static::$contextResolver !== null) {
            return (static::$contextResolver)();
        }

        return static::$defaultContext ??= new Context();
    }

    /**
     * Resolves the context of the current request, e.g. from a request scoped container binding
     * or the current coroutine. Pass null to go back to the default (one per PHP process).
     *
     * @param null|callable(): Context $resolver
     */
    public static function setContextResolver(?callable $resolver): void
    {
        static::$contextResolver = $resolver;
    }

    /**
     * Runs the callback with the given (or a fresh) context and restores the previous one afterwards,
     * even when the callback throws.
     *
     * @template T
     * @param callable(Context): T $callback
     * @return T
     */
    public static function withContext(callable $callback, ?Context $context = null): mixed
    {
        $context ??= new Context();
        $previousDefault = static::$defaultContext;
        $previousResolver = static::$contextResolver;

        static::$defaultContext = $context;
        static::$contextResolver = null;

        try {
            return $callback($context);
        } finally {
            static::$defaultContext = $previousDefault;
            static::$contextResolver = $previousResolver;
        }
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    protected function &jsmodsStore(): array
    {
        return $this->context->jsmods;
    }

    protected function &prioritiesStore(): array
    {
        return $this->context->priorities;
    }

    /**
     * @deprecated a pagelet adds itself to the page, see Context::addPagelet().
     */
    public static function addPagelet($id, Pagelet $pagelet): void
    {
        static::context()->addPagelet($id, $pagelet);
    }

    /**
     * The page of the current request, e.g. to call a JavaScript module once the page is loaded:
     * `BigPipe::page()->call('Page', 'init')`.
     */
    public static function page(): static
    {
        return new static();
    }

    public static function jsmods(): array
    {
        return static::context()->jsmods();
    }

    /**
     * Sends the CSRF token of the current request to the browser, which adds it to every request
     * that can change data (not GET, HEAD or OPTIONS) to the same origin, as the header and/or
     * the field of the data.
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     * @throws \Throwable
     */
    public static function setCSRFToken(string $token, ?string $header = 'X-CSRF-TOKEN', ?string $param = null): void
    {
        if ($token === '' || ($header === null && $param === null)) {
            throw new Exceptions\BigPipeInvalidArgumentException("Set a token and a header or a parameter for it.");
        }

        (new static())->define(static::CSRF_TOKEN_MODULE, [
            'token' => $token,
            'header' => $header,
            'param' => $param,
        ]);
    }

    /**
     * Sets the Content Security Policy nonce of the current request. BigPipe adds it to the inline
     * script it renders and defines it there as the CSPNonce module, which the browser part adds to
     * the stylesheets and scripts it loads. Only the page script defines it, never an AsyncResponse,
     * because the page keeps the nonce of its own policy.
     */
    public static function setNonce(?string $nonce): void
    {
        static::context()->nonce = $nonce;
    }

    /**
     * Returns the nonce attribute for an inline script (with a leading space), or an empty string.
     */
    public static function nonceAttribute(): string
    {
        return static::formatNonceAttribute(static::context()->nonce);
    }

    protected static function formatNonceAttribute(?string $nonce): string
    {
        return $nonce === null ? '' : ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES) . '"';
    }

    public static function render(): string
    {
        return (string) new static();
    }

    public static function reset(): void
    {
        static::context()->reset();
    }

    /**
     * Sends the pagelets one by one, each as soon as it is rendered, and flushes the output after
     * each of them. Call it at the end of the page, after the placeholders were printed: the page
     * printed so far is flushed first. Pagelets created while another one is rendered are sent too.
     *
     * @param null|callable(string): void $write gets every chunk, prints and flushes it by default
     * @throws \Throwable
     */
    public static function stream(?callable $write = null): void
    {
        (new static())->streamTo($write ?? static function (string $chunk): void {
            echo $chunk;

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        });
    }

    /**
     * @param callable(string): void $write
     * @throws \Throwable
     */
    public function streamTo(callable $write): void
    {
        $nonce = static::formatNonceAttribute($this->context->nonce);
        $tag = static fn (string $script): string => "<script$nonce>$script</script>\n";

        try {
            $defines = $this->takeDefines();
            $write(empty($defines) ? '' : $tag(static::handleScript(['define' => $defines])));

            while (($pagelet = $this->takeNextPagelet()) !== null) {

                $write($tag(static::arriveScript($pagelet->renderData())));
            }

            $last = [
                'id' => static::LAST_PAGELET_ID,
                'js' => [],
                'css' => [],
                'domops' => [],
                'jsmods' => ['require' => []],
                'is_last' => true,
            ];

            $jsmods = $this->context->jsmods();

            $write($tag(static::bootloadScript($jsmods) . static::arriveScript($last) . static::handleScript($jsmods)));
        } finally {
            $this->context->reset();
        }
    }

    /**
     * Takes the first pagelet of the lowest phase, also one created while another was rendered.
     */
    protected function takeNextPagelet(): ?Pagelet
    {
        $next = null;

        foreach ($this->context->pagelets as $id => $pagelet) {
            if ($next === null || $pagelet->getPhase() < $this->context->pagelets[$next]->getPhase()) {
                $next = $id;
            }
        }

        if ($next === null) {
            return null;
        }

        $pagelet = $this->context->pagelets[$next];
        unset($this->context->pagelets[$next]);

        return $pagelet;
    }

    protected static function arriveScript(array $data): string
    {
        return "(new (require(\"bigpipe-util/dist/BigPipe\"))).onPageletArrive(" . json_encode($data, JSON_THROW_ON_ERROR) . ");";
    }

    /**
     * Sends the resource map and the bootloadable modules the modules of the page call, before
     * the pagelets and the modules of the page.
     */
    protected static function bootloadScript(array $jsmods): string
    {
        $data = Bootloader::dataFor([], $jsmods);
        $require = [];

        if (isset($data['resource_map'])) {
            $require[] = [Bootloader::MODULE, 'setResourceMap', [$data['resource_map']]];
        }

        if (isset($data['bootloadable'])) {
            $require[] = [Bootloader::MODULE, 'enableBootload', [$data['bootloadable']]];
        }

        return empty($require) ? '' : static::handleScript(['require' => $require]) . "\n";
    }

    protected static function handleScript(array $jsmods): string
    {
        return "(new (require(\"bigpipe-util/dist/ServerJS\"))).handle(" . json_encode($jsmods, JSON_THROW_ON_ERROR) . ");";
    }

    /**
     * Takes the defines of the page out of its jsmods, with the nonce first: the pagelets can
     * require them, so they are sent before the pagelets.
     */
    protected function takeDefines(): array
    {
        $defines = $this->context->jsmods['define'] ?? [];
        unset($this->context->jsmods['define']);

        if ($this->context->nonce !== null) {
            array_unshift($defines, [static::CSP_NONCE_MODULE, $this->context->nonce]);
        }

        return $defines;
    }

    public function __toString(): string
    {
        try {
            $pageletsScript = '';
            $pagelets = $this->context->pagelets;
            uasort($pagelets, static fn (Pagelet $a, Pagelet $b): int => $a->getPhase() <=> $b->getPhase());

            foreach ($pagelets as $i => $pagelet) {
                $data = $pagelet->renderData();

                if (array_key_last($pagelets) === $i) {
                    $data['is_last'] = true;
                }

                $pageletsScript .= static::arriveScript($data) . "\n";
            }

            $defines = $this->takeDefines();
            $script = empty($defines) ? '' : static::handleScript(['define' => $defines]) . "\n";
            $jsmods = $this->context->jsmods();
            $script .= static::bootloadScript($jsmods) . $pageletsScript . static::handleScript($jsmods);
        } finally {
            $this->context->reset();
        }

        $nonce = static::formatNonceAttribute($this->context->nonce);

        return <<<HTML
<script$nonce>
$script
</script>
HTML;

    }
}
