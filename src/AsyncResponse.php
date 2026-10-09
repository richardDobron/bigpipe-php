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

    public array $domops = [];

    public mixed $payload = [];

    /** @var null|array{error: int, errorSummary: string, errorDescription: string, errorIsWarning: bool, isTransient: bool} */
    protected ?array $error = null;

    private BigPipe $bigPipe;

    private TransportMarker $transport;

    public function __construct(?Context $context = null)
    {
        $this->bigPipe = new BigPipe($context);
        $this->transport = new TransportMarker();
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
            $transport = $this->transport->transportHtml($html);
        }

        $this->domops[] = [
            $method,
            $selector,
            !$selector,
            $transport
        ];

        return $this;
    }

    public function bigPipe(): BigPipe
    {
        return $this->bigPipe;
    }

    public function transport(): TransportMarker
    {
        return $this->transport;
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

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Define eval script to evaluate
     *
     * @param string $context
     * @param string $code
     *
     * @return static
     */
    public function eval(string $context, string $code): static
    {
        return $this->defineDomOp($context, $code, self::DOM_EVAL);
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
            $this->bigPipe()->require("require('bigpipe-util/dist/core/ReloadPage').delay()", [
                $delay,
            ]);
        } else {
            $this->bigPipe()->require("require('bigpipe-util/dist/core/ReloadPage').now()");
        }

        return $this;
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
        $this->bigPipe()->require("require('bigpipe-util/dist/core/ServerRedirect').redirectPageTo()", [
            $url,
            $delay,
        ]);

        return $this;
    }

    /**
     * Get response
     *
     * @return array<string, mixed>
     */
    public function getResponse(): array
    {
        return [
            "payload" => $this->payload,
            "domops" => $this->domops,
            "jsmods" => $this->bigPipe->getContext()->jsmods(),
            "__ar" => 1,
        ] + ($this->error ?? []);
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
     * Send response
     *
     * @return mixed
     */
    public function send(): mixed
    {
        header("content-type: text/javascript");

        echo $this->buildResponseString();

        exit();
    }
}
