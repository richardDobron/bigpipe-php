<?php

namespace dobron\BigPipe;

class Pagelet
{
    use JsMods;

    protected string $id;
    protected string $element;
    protected string $content = '';
    protected array $jsmods = [
        'require' => [],
    ];
    protected array $js = [];
    protected array $css = [];
    protected array $onloads = [];
    protected array $onafterload = [
        'require' => [],
    ];
    protected array $priorities = [];
    /** @var list<callable(Pagelet): mixed> */
    protected array $deferred = [];

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->element = generate_unique_node_id();

        BigPipe::addPagelet($id, $this);
    }

    public function jsmods(): array
    {
        array_multisort($this->priorities, $this->jsmods['require']);

        return $this->jsmods;
    }

    public function appendContent(string $stringOrFile, bool $isFile = false): static
    {
        if ($isFile) {
            ob_start();
            require $stringOrFile;
            $this->content .= ob_get_contents();
            ob_end_clean();
        } else {
            $this->content .= $stringOrFile;
        }

        return $this;
    }

    /**
     * Renders content when the pagelet is rendered, not when it is created, so a streamed page can
     * be sent before its pagelets are ready. The callable gets the pagelet, e.g. to add CSS or
     * modules; what it prints and returns is appended to the content.
     *
     * @param callable(Pagelet): mixed $content
     */
    public function defer(callable $content): static
    {
        $this->deferred[] = $content;

        return $this;
    }

    public function renderContent(): string
    {
        while ($content = array_shift($this->deferred)) {
            ob_start();

            try {
                $returned = $content($this);
            } catch (\Throwable $exception) {
                ob_end_clean();

                throw $exception;
            }

            $this->content .= ob_get_clean();

            if (is_string($returned) || $returned instanceof \Stringable) {
                $this->content .= $returned;
            }
        }

        return $this->content;
    }

    /**
     * @deprecated The code is sent as an eval DOM operation, which a Content Security Policy without
     *             'unsafe-eval' blocks in the browser. Call a JavaScript module with require() or
     *             onAfterLoad() instead.
     */
    public function addOnload(string $code): static
    {
        trigger_error(
            __METHOD__ . "() is deprecated, call a JavaScript module with require() or onAfterLoad() instead.",
            E_USER_DEPRECATED
        );

        $this->onloads[] = $code;

        return $this;
    }

    /**
     * Calls a JavaScript module once every pagelet has run its jsmods and the window has loaded,
     * for work that must not compete with the page, like prefetching or analytics.
     *
     * @param string|array{0: string, 1?: string} $fragment
     * @param array $args
     * @return static
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function onAfterLoad(string|array $fragment, array $args = []): static
    {
        if (!static::isValidRequireCall($fragment)) {
            throw new Exceptions\BigPipeInvalidArgumentException("Invalid call.");
        }

        $fragmentParts = static::parseRequireCall($fragment);
        $require = [
            $fragmentParts['module'],
            $fragmentParts['method'] ?? null,
        ];

        if (!empty($args)) {
            $require[] = static::transformObjectString($args);
        }

        $this->onafterload['require'][] = array_trim($require);

        return $this;
    }

    public function addJs(string $file): static
    {
        $this->js[] = $file;

        return $this;
    }

    public function addCss(string $file): static
    {
        $this->css[] = $file;

        return $this;
    }

    public function renderData(): array
    {
        $domops = [
            [
                'setContent',
                '#' . $this->element,
                false,
                [
                    '__html' => $this->renderContent(),
                ]
            ]
        ];

        foreach ($this->onloads as $code) {
            $domops[] = [
                'eval',
                'body',
                false,
                $code,
            ];
        }

        $data = [
            'id' => $this->id,
            'js' => $this->js,
            'css' => $this->css,
            "domops" => $domops,
            'jsmods' => $this->jsmods(),
        ];

        if (!empty($this->onafterload['require'])) {
            $data['onafterload'] = $this->onafterload;
        }

        return $data;
    }

    protected function &jsmodsStore(): array
    {
        return $this->jsmods;
    }

    protected function &prioritiesStore(): array
    {
        return $this->priorities;
    }

    public function render(): string
    {
        return $this;
    }

    public function __toString(): string
    {
        return "<div id=\"" . $this->element . "\"></div>";
    }
}
