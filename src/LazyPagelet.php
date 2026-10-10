<?php

namespace dobron\BigPipe;

/**
 * The placeholder of a pagelet that the browser loads from a URL: when it becomes visible
 * (default), when the browser is idle, or right away. Like UIPagelet of Facebook, it prints the
 * root element of the pagelet and calls the UIPagelet module with it. The endpoint responds with
 * the pagelet, e.g. `(new AsyncResponse())->pagelet(new FeedPagelet())`.
 *
 * Printed while a pagelet is rendered, the call is added to that pagelet, so it runs once the
 * placeholder is on the page.
 */
class LazyPagelet
{
    public const LOAD_VISIBLE = 'visible';
    public const LOAD_IDLE = 'idle';
    public const LOAD_EAGER = 'load';

    public const MODULE = 'bigpipe-util/dist/UIPagelet';

    protected bool $printed = false;

    /**
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public function __construct(
        protected string $pageletId,
        protected string $url,
        protected array $data = [],
        protected string $placeholder = '',
        protected string $load = self::LOAD_VISIBLE
    ) {
        Pagelet::assertValidId($pageletId);
    }

    public function getId(): string
    {
        return $this->pageletId;
    }

    /**
     * Data sent with the request, e.g. the page of a feed.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function setPlaceholder(string $html): static
    {
        $this->placeholder = $html;

        return $this;
    }

    public function setLoad(string $load): static
    {
        $this->load = $load;

        return $this;
    }

    public function render(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        $rootId = Pagelet::rootId($this->pageletId);

        if (!$this->printed) {
            $this->printed = true;

            (Pagelet::current() ?? new BigPipe())->require(
                [static::MODULE, 'loadFromEndpoint'],
                [$this->url, $rootId, $this->data, ['load' => $this->load]]
            );
        }

        return '<div id="' . $rootId . '">' . $this->placeholder . '</div>';
    }
}
