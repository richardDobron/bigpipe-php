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

    /**
     * @var array
     */
    public $domops = [];
    /**
     * @var null|array
     */
    public $payload = [];
    /**
     * @var BigPipe
     */
    private $bigPipe;
    /**
     * @var TransportMarker
     */
    private $transport;

    public function __construct()
    {
        $this->bigPipe = new BigPipe();
        $this->transport = new TransportMarker();
    }

    /**
     * Add a shield to prevent "JSON Hijacking" attacks where an attacker
     * requests a JSON response using a normal <script /> tag and then uses
     * Object.prototype.__defineSetter__() or similar to read response data.
     * This header causes the browser to loop infinitely instead of handing over
     * sensitive data.
     *
     * @param string $jsonResponse
     * @return string
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
     * @return self
     */
    private function defineDomOp(string $selector, ?string $html, string $method): self
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
     * @return self
     */
    public function setPayload($data): self
    {
        $this->payload = $data;

        return $this;
    }

    /**
     * Define eval script to evaluate
     *
     * @param string $context
     * @param string $code
     *
     * @return self
     */
    public function eval(string $context, string $code): self
    {
        return $this->defineDomOp($context, $code, self::DOM_EVAL);
    }

    /**
     * Define hide DOM operation
     *
     * @param string $selector
     *
     * @return self
     */
    public function hide(string $selector): self
    {
        return $this->defineDomOp($selector, null, self::DOM_HIDE);
    }

    /**
     * Define show DOM operation
     *
     * @param string $selector
     *
     * @return self
     */
    public function show(string $selector): self
    {
        return $this->defineDomOp($selector, null, self::DOM_SHOW);
    }

    /**
     * Define set content DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return self
     */
    public function setContent(string $selector, ?string $html): self
    {
        return $this->defineDomOp($selector, $html, self::DOM_SET_CONTENT);
    }

    /**
     * Define append content DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return self
     */
    public function appendContent(string $selector, ?string $html): self
    {
        return $this->defineDomOp($selector, $html, self::DOM_APPEND_CONTENT);
    }

    /**
     * Define prepend content DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return self
     */
    public function prependContent(string $selector, ?string $html): self
    {
        return $this->defineDomOp($selector, $html, self::DOM_PREPEND_CONTENT);
    }

    /**
     * Define insert after DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return self
     */
    public function insertAfter(string $selector, ?string $html): self
    {
        return $this->defineDomOp($selector, $html, self::DOM_INSERT_AFTER);
    }

    /**
     * Define insert before DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return self
     */
    public function insertBefore(string $selector, ?string $html): self
    {
        return $this->defineDomOp($selector, $html, self::DOM_INSERT_BEFORE);
    }

    /**
     * Define replace DOM operation
     *
     * @param string      $selector
     * @param string|null $html
     *
     * @return self
     */
    public function replace(string $selector, ?string $html): self
    {
        return $this->defineDomOp($selector, $html, self::DOM_REPLACE);
    }

    /**
     * Define remove DOM operation
     *
     * @param string $selector
     *
     * @return self
     */
    public function remove(string $selector): self
    {
        return $this->defineDomOp($selector, null, self::DOM_REMOVE);
    }

    /**
     * Reload a page.
     *
     * @param int $delay
     *
     * @return self
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function reload(int $delay = 0): self
    {
        if ($delay > 0) {
            $this->bigPipe()->require("require('bigpipe-util/src/core/ReloadPage').delay()", [
                $delay,
            ]);
        } else {
            $this->bigPipe()->require("require('bigpipe-util/src/core/ReloadPage').now()");
        }

        return $this;
    }

    /**
     * Redirect to the given url.
     *
     * @param string $url
     * @param int $delay
     *
     * @return self
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function redirect(string $url, int $delay = 0): self
    {
        $this->bigPipe()->require("require('bigpipe-util/src/core/ServerRedirect').redirectPageTo()", [
            $url,
            $delay,
        ]);

        return $this;
    }

    /**
     * Get response
     *
     * @return array
     */
    public function getResponse(): array
    {
        return [
            "payload" => $this->payload,
            "domops" => $this->domops,
            "jsmods" => BigPipe::jsmods(),
            "__ar" => 1,
        ];
    }

    /**
     * Get response as string
     *
     * @return string
     */
    public function buildResponseString(): string
    {
        $jsonResponse = json_encode($this->getResponse());

        return $this->addJSONShield($jsonResponse);
    }

    /**
     * Send response
     *
     * @return void
     */
    public function send()
    {
        header("content-type: text/javascript");

        echo $this->buildResponseString();

        exit();
    }
}
