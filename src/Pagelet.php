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
    protected int $phase = 0;
    /** @var list<string> */
    protected array $displayDependency = [];
    /** @var list<callable(Pagelet): mixed> */
    protected array $deferred = [];
    /** @var list<Pagelet> */
    private static array $rendering = [];

    /**
     * A pagelet class renders its own content in content(), and can declare its id, CSS and JS:
     *
     *     class FeedPagelet extends Pagelet
     *     {
     *         protected array $css = ['/css/feed.css'];
     *
     *         protected function content(): string
     *         {
     *             return renderFeed();
     *         }
     *     }
     *
     * Without an id, a pagelet class gets one from its name, e.g. "user_profile" for
     * UserProfilePagelet.
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function __construct(?string $id = null)
    {
        if ($id === null && !isset($this->id) && static::class === self::class) {
            throw new Exceptions\BigPipeInvalidArgumentException("A pagelet needs an id.");
        }

        $this->id = $id ?? (isset($this->id) ? $this->id : static::defaultId());

        static::assertValidId($this->id);

        $this->element = static::rootId($this->id);
        $this->deferred[] = fn () => $this->content();

        BigPipe::context()->addPagelet($this->id, $this);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The id of the element a pagelet is rendered in, "pagelet_feed" for the pagelet "feed".
     */
    public static function rootId(string $pageletId): string
    {
        return 'pagelet_' . $pageletId;
    }

    /**
     * Returns the placeholder of this pagelet class that the browser loads from the URL, when it
     * becomes visible by default. The endpoint responds with the pagelet, e.g.
     * `(new AsyncResponse())->pagelet(new FeedPagelet())`.
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public static function lazy(
        string $url,
        array $data = [],
        string $placeholder = '',
        string $load = LazyPagelet::LOAD_VISIBLE
    ): LazyPagelet {
        if (static::class === self::class) {
            throw new Exceptions\BigPipeInvalidArgumentException("A pagelet needs an id, use new LazyPagelet().");
        }

        $id = (new \ReflectionClass(static::class))->getDefaultProperties()['id'] ?? null;

        return new LazyPagelet(is_string($id) ? $id : static::defaultId(), $url, $data, $placeholder, $load);
    }

    /**
     * The pagelet whose content is being rendered, e.g. to add the modules of what its content
     * prints to it.
     */
    public static function current(): ?Pagelet
    {
        return self::$rendering ? self::$rendering[array_key_last(self::$rendering)] : null;
    }

    /**
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public static function assertValidId(string $id): void
    {
        if (!preg_match('/^[A-Za-z][\w-]*$/', $id)) {
            throw new Exceptions\BigPipeInvalidArgumentException(
                "Invalid pagelet id \"$id\": use letters, digits, \"_\" and \"-\", starting with a letter."
            );
        }
    }

    /**
     * Renders the content of a pagelet class when the pagelet is rendered, like defer(). What it
     * prints and returns is appended to the content. Without a return type, so a subclass can
     * declare string or void.
     *
     * @return mixed
     */
    protected function content()
    {
        return null;
    }

    protected static function defaultId(): string
    {
        $name = substr(strrchr('\\' . static::class, '\\'), 1);
        $name = preg_replace('/Pagelet$/', '', $name) ?: $name;

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    /**
     * @internal the modules of the pagelet, sorted by priority
     */
    public function jsmods(): array
    {
        array_multisort($this->priorities, $this->jsmods['require']);

        return $this->jsmods;
    }

    /**
     * Appends HTML to the content.
     *
     * @param bool $isFile deprecated, use appendFile()
     */
    public function appendContent(string $stringOrFile, bool $isFile = false): static
    {
        if ($isFile) {
            return $this->appendFile($stringOrFile);
        }

        $this->content .= $stringOrFile;

        return $this;
    }

    /**
     * Includes the PHP file and appends what it prints to the content.
     */
    public function appendFile(string $path): static
    {
        self::$rendering[] = $this;
        ob_start();

        try {
            require $path;
            $this->content .= ob_get_contents();
        } finally {
            ob_end_clean();
            array_pop(self::$rendering);
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
            self::$rendering[] = $this;
            ob_start();

            try {
                $returned = $content($this);
            } catch (\Throwable $exception) {
                ob_end_clean();

                throw $exception;
            } finally {
                array_pop(self::$rendering);
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

    /**
     * The browser displays the pagelet after the pagelets of a lower phase, e.g. the content of the
     * page in phase 0 (default) before the sidebar and ads in phase 1. BigPipe sends the pagelets
     * in the order of their phases.
     */
    public function setPhase(int $phase): static
    {
        $this->phase = $phase;

        return $this;
    }

    public function getPhase(): int
    {
        return $this->phase;
    }

    /**
     * The browser displays the pagelet after the given pagelets, waiting for them to arrive.
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function displayAfter(string|Pagelet ...$pagelets): static
    {
        foreach ($pagelets as $pagelet) {
            $id = $pagelet instanceof Pagelet ? $pagelet->getId() : $pagelet;
            static::assertValidId($id);

            if (!in_array($id, $this->displayDependency, true)) {
                $this->displayDependency[] = $id;
            }
        }

        return $this;
    }

    /**
     * @internal the data BigPipe sends to the browser
     */
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

        if ($this->phase !== 0) {
            $data['phase'] = $this->phase;
        }

        if (!empty($this->displayDependency)) {
            $data['display_dependency'] = $this->displayDependency;
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

    /**
     * The placeholder of the pagelet, its root element.
     */
    public function placeholder(): string
    {
        return (string) $this;
    }

    /**
     * @deprecated use placeholder(), the content is rendered by content()
     */
    public function render(): string
    {
        return $this->placeholder();
    }

    public function __toString(): string
    {
        return "<div id=\"" . $this->element . "\"></div>";
    }
}
