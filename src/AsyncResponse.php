<?php

namespace dobron\BigPipe;

class AsyncResponse
{
    /**
     * DOM operations
     */
    public const DOM_EVAL = "eval";
    public const DOM_HIDE = "hide";
    public const DOM_SHOW = "show";
    public const DOM_SET_CONTENT = "setContent";
    public const DOM_APPEND_CONTENT = "appendContent";
    public const DOM_PREPEND_CONTENT = "prependContent";
    public const DOM_INSERT_AFTER = "insertAfter";
    public const DOM_INSERT_BEFORE = "insertBefore";
    public const DOM_REMOVE = "remove";
    public const DOM_REPLACE = "replace";
    public const DOM_MORPH = "morph";
    public const DOM_MORPH_CONTENT = "morphContent";

    public const CONTENT_TYPE = 'application/json; charset=utf-8';

    /** The request parameter of a request that accepts a streamed response, see stream(). */
    public const STREAM_PARAM = '__stream';

    /** Separates the parts of a streamed response. */
    public const STREAM_DELIMITER = '/*<!-- fetch-stream -->*/';

    protected array $domops = [];

    protected mixed $payload = [];

    /** @var null|array{error: int, errorSummary: string, errorDescription: string, errorIsWarning: bool, isTransient: bool} */
    protected ?array $error = null;
    protected bool $csrfRefresh = false;

    /** @var list<array<string, mixed>> */
    protected array $pagelets = [];

    private BigPipe $bigPipe;

    /** Whether the pagelets of the context are sent too, see transition(). */
    private bool $contextPagelets = false;

    public function __construct(?Context $context = null)
    {
        $this->bigPipe = new BigPipe($context);
    }

    /**
     * Add a shield to prevent "JSON Hijacking" attacks where an attacker
     * requests a JSON response using a normal <script /> tag and then uses
     * Object.prototype.__defineSetter__() or similar to read response data.
     * This header causes the browser to loop infinitely instead of handing over
     * sensitive data.
     */
    private function addJSONShield(string $jsonResponse): string
    {
        $shield = "for (;;);";

        return $shield . $jsonResponse;
    }

    /**
     * Define DOM operation
     *
     * @param string $selector
     * @param string|null $html
     * @param string $method
     *
     * @return static
     */
    private function defineDomOp(string $selector, ?string $html, string $method): static
    {
        $transport = null;

        if ($method === self::DOM_EVAL) {
            $transport = $html;
        } elseif (!in_array($method, [self::DOM_HIDE, self::DOM_SHOW, self::DOM_REMOVE], true)) {
            $transport = TransportMarker::html($html);
        }

        $this->domops[] = [
            $method,
            $selector,
            !$selector,
            $transport
        ];

        return $this;
    }

    /**
     * The BigPipe of the response, which shares its context: its module calls and pagelets are sent
     * with the response.
     */
    public function bigPipe(): BigPipe
    {
        return $this->bigPipe;
    }

    /**
     * @deprecated the methods of TransportMarker are static, e.g. TransportMarker::element().
     */
    public function transport(): TransportMarker
    {
        return new TransportMarker();
    }

    /**
     * Calls a JavaScript module in the browser, see BigPipe::call().
     *
     * @throws \Throwable
     */
    public function call(string $module, ?string $method = null, array $args = [], ?int $priority = null): static
    {
        $this->bigPipe->call($module, $method, $args, $priority);

        return $this;
    }

    /**
     * @deprecated as a string like "require('Module').method()", use call() instead.
     *
     * @param string|array{0: string, 1?: string} $fragment
     * @throws \Throwable
     */
    public function require(string|array $fragment, array $args = [], ?int $priority = null): static
    {
        $this->bigPipe->require($fragment, $args, $priority);

        return $this;
    }

    /**
     * Sends data the browser can require as a module, see BigPipe::define().
     *
     * @throws \Throwable
     */
    public function define(string $module, mixed $exports): static
    {
        $this->bigPipe->define($module, $exports);

        return $this;
    }

    /**
     * Defines a module that is an element of the page, see JsMods::defineElement().
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function defineElement(string $module, string $elementId): static
    {
        $this->bigPipe->defineElement($module, $elementId);

        return $this;
    }

    /**
     * Defines an object the browser creates once and shares, see BigPipe::instance().
     *
     * @throws \Throwable
     */
    public function instance(string $module, array $args = []): Instance
    {
        return $this->bigPipe->instance($module, $args);
    }

    /**
     * Set payload
     *
     * @param mixed $data
     * @return static
     */
    public function setPayload(mixed $data): static
    {
        $this->payload = $data;

        return $this;
    }

    /**
     * Marks the response as failed: the browser calls the error handler of the request instead of
     * its handler. The DOM operations and modules of the response are applied anyway, e.g. to mark
     * an invalid field.
     *
     * @param string $summary a short title of the error
     * @param string $description a sentence for the user
     * @param int $code an application specific code, not 0
     * @param bool $isWarning the error is only a warning
     * @param bool $isTransient trying again may help
     *
     * @return static
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function setError(
        string $summary,
        string $description = '',
        int $code = 1,
        bool $isWarning = false,
        bool $isTransient = false
    ): static {
        if ($code === 0) {
            throw new Exceptions\BigPipeInvalidArgumentException("The error code must not be 0.");
        }

        $this->error = [
            "error" => $code,
            "errorSummary" => $summary,
            "errorDescription" => $description,
            "errorIsWarning" => $isWarning,
            "isTransient" => $isTransient,
        ];

        return $this;
    }

    /**
     * Tells the browser that the request was rejected because of its CSRF token, e.g. one that
     * expired with the session, and gives it the new token: the browser sends the request again,
     * once, and calls the handlers of the request for that response. See BigPipe::setCSRFToken().
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     * @throws \Throwable
     */
    public function retryWithCSRFToken(
        string $token,
        ?string $header = 'X-CSRF-TOKEN',
        ?string $param = null,
        ?string $refreshUri = null
    ): static {
        $this->define(BigPipe::CSRF_TOKEN_MODULE, BigPipe::csrfTokenModule($token, $header, $param, $refreshUri));
        $this->csrfRefresh = true;

        return $this;
    }

    /**
     * Whether the response was marked as failed with setError().
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Define eval script to evaluate
     *
     * @deprecated A Content Security Policy without 'unsafe-eval' blocks it in the browser,
     *             call a JavaScript module with $response->call() instead.
     *
     * @param string $selector
     * @param string $code
     *
     * @return static
     */
    public function eval(string $selector, string $code): static
    {
        trigger_error(
            __METHOD__ . "() is deprecated, call a JavaScript module with call() instead.",
            E_USER_DEPRECATED
        );

        return $this->defineDomOp($selector, $code, self::DOM_EVAL);
    }

    /**
     * Define hide DOM operation
     *
     * @param string $selector
     *
     * @return static
     */
    public function hide(string $selector): static
    {
        return $this->defineDomOp($selector, null, self::DOM_HIDE);
    }

    /**
     * Define show DOM operation
     *
     * @param string $selector
     *
     * @return static
     */
    public function show(string $selector): static
    {
        return $this->defineDomOp($selector, null, self::DOM_SHOW);
    }

    /**
     * Define set content DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function setContent(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_SET_CONTENT);
    }

    /**
     * Define append content DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function appendContent(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_APPEND_CONTENT);
    }

    /**
     * Define prepend content DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function prependContent(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_PREPEND_CONTENT);
    }

    /**
     * Define insert after DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function insertAfter(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_INSERT_AFTER);
    }

    /**
     * Define insert before DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function insertBefore(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_INSERT_BEFORE);
    }

    /**
     * Define replace DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function replace(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_REPLACE);
    }

    /**
     * Define morph DOM operation: updates the element to match the HTML while keeping the existing
     * elements, so focus, form values and element state survive.
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function morph(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_MORPH);
    }

    /**
     * Define morph content DOM operation: like morph(), but for the children of the element only.
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return static
     */
    public function morphContent(string $selector, ?string $html): static
    {
        return $this->defineDomOp($selector, $html, self::DOM_MORPH_CONTENT);
    }

    /**
     * Define remove DOM operation
     *
     * @param string $selector
     *
     * @return static
     */
    public function remove(string $selector): static
    {
        return $this->defineDomOp($selector, null, self::DOM_REMOVE);
    }

    /**
     * Reload a page.
     *
     * @param int $delay
     *
     * @return static
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function reload(int $delay = 0): static
    {
        if ($delay > 0) {
            return $this->call('bigpipe-util/dist/core/ReloadPage', 'delay', [$delay]);
        }

        return $this->call('bigpipe-util/dist/core/ReloadPage', 'now');
    }

    /**
     * Redirect to the given url.
     *
     * @param string $url
     * @param int $delay
     *
     * @return static
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function redirect(string $url, int $delay = 0): static
    {
        $this->call('bigpipe-util/dist/core/ServerRedirect', 'redirectPageTo', [$url, $delay]);

        return $this;
    }

    /**
     * Get response
     *
     * @return array<string, mixed>
     */
    public function getResponse(): array
    {
        $response = [
            "payload" => $this->payload,
            "domops" => $this->domops,
        ];

        if ($this->contextPagelets) {
            $this->contextPagelets = false;
            $pagelets = [];

            if ($this->bigPipe->getContext()->parallel && Parallel::isAvailable()) {
                Parallel::run(
                    fn (): ?Pagelet => $this->bigPipe->takeNextPagelet(),
                    static function (Pagelet $pagelet, array $data) use (&$pagelets): void {
                        $pagelets[] = $data;
                    }
                );
            } else {
                while (($pagelet = $this->bigPipe->takeNextPagelet()) !== null) {
                    $pagelets[] = $pagelet->renderData();
                }
            }

            if (!empty($pagelets)) {
                $pagelets[array_key_last($pagelets)]['is_last'] = true;
                array_push($this->pagelets, ...$pagelets);
            }
        }

        if (!empty($this->pagelets)) {
            $response["pagelets"] = $this->sortedPagelets();
        }

        $jsmods = $this->bigPipe->getContext()->jsmods();

        return $response + Bootloader::dataFor([], $jsmods) + [
            "jsmods" => $jsmods,
            "__ar" => 1,
        ] + ($this->csrfRefresh ? ['csrf_refresh' => 1] : []) + ($this->error ?? []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sortedPagelets(): array
    {
        $pagelets = $this->pagelets;
        usort($pagelets, static fn (array $a, array $b): int => ($a['phase'] ?? 0) <=> ($b['phase'] ?? 0));

        return $pagelets;
    }

    /**
     * Sends a pagelet, e.g. a pagelet class loaded lazily: it replaces the element matching the
     * selector, or without one the element that sent the request, like the placeholder of a
     * LazyPagelet. The browser loads its CSS and JS and runs its modules like on a page.
     *
     * @throws \Throwable
     */
    public function pagelet(Pagelet $pagelet, string $selector = ''): static
    {
        $this->replace($selector, (string) $pagelet);
        unset($this->bigPipe->getContext()->pagelets[$pagelet->getId()]);

        $data = $pagelet->renderData();
        $data['is_last'] = true;
        $this->pagelets[] = $data;

        return $this;
    }

    /**
     * Answers a page transition, see Quickling: the content fills the canvas, and the pagelets
     * printed in it are sent in the order of their phases. The payload has the version of the
     * pages, the title and the class of `<body>`.
     *
     * @throws \Throwable
     */
    public function transition(string $content, ?string $title = null, string $bodyClass = ''): static
    {
        $this->setContent('', $content);
        $this->setTransitionPayload([
            'title' => $title,
            'body_class' => $bodyClass,
            'version' => Quickling::version(),
            'uri' => $this->requestUri(),
        ]);

        $this->contextPagelets = true;

        return $this;
    }

    /**
     * Sends a page transition to another URL instead, e.g. after a login. The browser loads it with
     * a page transition, or in full when it must not handle it or with `$force`.
     */
    public function transitionRedirect(string $url, bool $force = false): static
    {
        return $this->setTransitionPayload(array_filter(['redirect' => $url, 'force' => $force]));
    }

    private function setTransitionPayload(array $data): static
    {
        $this->payload = array_merge(is_array($this->payload) ? $this->payload : [], $data);

        return $this;
    }

    private function requestUri(): ?string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        if (!is_string($uri)) {
            return null;
        }

        $parts = parse_url($uri) ?: [];
        parse_str($parts['query'] ?? '', $query);
        unset($query[Quickling::PARAM], $query['__req'], $query[static::STREAM_PARAM]);

        return ($parts['path'] ?? '/') . (empty($query) ? '' : '?' . http_build_query($query));
    }

    /**
     * Renders a pagelet that is on the page again, e.g. new FeedPagelet() after a post was added:
     * it replaces the root element of the pagelet with the same id.
     *
     * @throws \Throwable
     */
    public function refreshPagelet(Pagelet $pagelet): static
    {
        return $this->pagelet($pagelet, '#' . Pagelet::rootId($pagelet->getId()));
    }

    /**
     * Get response as string
     *
     * @return string
     */
    public function buildResponseString(): string
    {
        try {
            $jsonResponse = json_encode($this->getResponse(), JSON_THROW_ON_ERROR);
        } finally {
            $this->bigPipe->getContext()->reset();
        }

        return $this->addJSONShield($jsonResponse);
    }

    /**
     * The headers of a response: JSON (behind the for (;;); shield), which the browser must not
     * run as a script.
     *
     * @return array<string, string>
     */
    public static function headers(): array
    {
        return [
            'Content-Type' => static::CONTENT_TYPE,
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    /**
     * Sends the headers and prints the response, but unlike send(), it doesn't end the script, so
     * a framework or a middleware can finish the request.
     */
    public function output(): void
    {
        if (!headers_sent()) {
            foreach (static::headers() as $name => $value) {
                header("$name: $value");
            }
        }

        echo $this->buildResponseString();
    }

    /**
     * Whether the request accepts a streamed response, see stream().
     */
    public static function isStreamRequested(): bool
    {
        return isset($_REQUEST[static::STREAM_PARAM]);
    }

    /**
     * Sends the response in parts, each as soon as it is ready: first the payload, the DOM
     * operations and the defines, then every pagelet as soon as it is rendered, in the order of
     * their phases, and last the modules. The browser shows every part when it arrives. Only for a
     * request that accepts it, see isStreamRequested().
     *
     * @param null|callable(string): void $write gets every part; without it, the headers are sent
     *                                      and every part is printed and flushed
     * @throws \Throwable
     */
    public function stream(?callable $write = null): void
    {
        if ($write === null) {
            if (!headers_sent()) {
                foreach (static::headers() as $name => $value) {
                    header("$name: $value");
                }
            }

            $write = static function (string $part): void {
                echo $part;

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };
        }

        $context = $this->bigPipe->getContext();
        $part = static function (array $content, bool $finished) use ($write): void {
            $write(json_encode(['content' => $content, 'finished' => $finished], JSON_THROW_ON_ERROR) . static::STREAM_DELIMITER);
        };

        try {
            $first = ['payload' => $this->payload, 'domops' => $this->domops] + ($this->error ?? []);
            if (!empty($context->jsmods['define'])) {
                $first['jsmods'] = ['define' => $context->jsmods['define']];
                unset($context->jsmods['define']);
            }
            $part($first, false);

            $sent = false;
            foreach ($this->sortedPagelets() as $data) {
                unset($data['is_last']);
                $part(['pagelets' => [$data]], false);
                $sent = true;
            }

            if ($this->contextPagelets && $context->parallel && Parallel::isAvailable()) {
                Parallel::run(
                    fn (): ?Pagelet => $this->bigPipe->takeNextPagelet(),
                    static function (Pagelet $pagelet, array $data) use ($part, &$sent): void {
                        $part(['pagelets' => [$data]], false);
                        $sent = true;
                    }
                );
            } else {
                while ($this->contextPagelets && ($pagelet = $this->bigPipe->takeNextPagelet()) !== null) {
                    $part(['pagelets' => [$pagelet->renderData()]], false);
                    $sent = true;
                }
            }

            $last = [];
            if ($sent) {
                $last['pagelets'] = [[
                    'id' => BigPipe::LAST_PAGELET_ID,
                    'js' => [],
                    'css' => [],
                    'domops' => [],
                    'jsmods' => ['require' => []],
                    'is_last' => true,
                ]];
            }

            $jsmods = $context->jsmods();
            $part($last + Bootloader::dataFor([], $jsmods) + ['jsmods' => $jsmods], true);
        } finally {
            $this->contextPagelets = false;
            $context->reset();
        }
    }

    /**
     * Sends the response, streamed when the request accepts it, and ends the script.
     *
     * @return mixed
     */
    public function send(): mixed
    {
        if (static::isStreamRequested()) {
            $this->stream();
        } else {
            $this->output();
        }

        exit();
    }
}
