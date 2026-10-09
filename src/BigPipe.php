<?php

namespace dobron\BigPipe;

class BigPipe
{
    use JsMods;

    public const CSRF_TOKEN_MODULE = 'CSRFToken';

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

    public static function addPagelet($id, Pagelet $pagelet): void
    {
        static::context()->addPagelet($id, $pagelet);
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
     * script it renders, and the browser part copies it to the stylesheets and scripts it loads.
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

    public function __toString(): string
    {
        try {
            $script = '';
            $pagelets = $this->context->pagelets;

            foreach ($pagelets as $i => $pagelet) {
                $data = $pagelet->renderData();

                if (array_key_last($pagelets) === $i) {
                    $data['is_last'] = true;
                }

                $script .= "(new (require(\"bigpipe-util/dist/BigPipe\"))).onPageletArrive(" . json_encode($data, JSON_THROW_ON_ERROR) . ");\n";
            }

            $script .= "(new (require(\"bigpipe-util/dist/ServerJS\"))).handle(" . json_encode($this->context->jsmods(), JSON_THROW_ON_ERROR) . ");";
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
