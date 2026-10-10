<?php

namespace dobron\BigPipe;

/**
 * A "See more" link that loads the next page of a list when it scrolls into view, like the
 * MorePagerFetchOnScroll of Facebook. It's a plain link handled by the Primer (rel="async"), so it
 * works when clicked too, and without JavaScript it leads to the URL.
 *
 * The endpoint appends the items and replaces the pager, the element that sent the request, with
 * the pager of the following page, or removes it:
 *
 *     $response->appendContent('ul.posts', $posts);
 *     $response->replace('', $hasMore ? (string) new MorePager('/feed?page=3') : '');
 */
class MorePager
{
    public const MODULE = 'bigpipe-util/dist/MorePagerFetchOnScroll';

    protected string $id;
    protected bool $printed = false;

    /**
     * @param string $url the next page
     * @param string $label HTML of the link
     * @param int $offset pixels before the link is visible to start loading
     */
    public function __construct(
        protected string $url,
        protected string $label = 'See more',
        protected int $offset = 300
    ) {
        $this->id = generate_unique_node_id();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function render(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        if (!$this->printed) {
            $this->printed = true;

            (Pagelet::current() ?? new BigPipe())->require(
                [static::MODULE],
                [TransportMarker::transportElement($this->id), $this->offset]
            );
        }

        $url = htmlspecialchars($this->url, ENT_QUOTES);

        return "<a id=\"{$this->id}\" href=\"{$url}\" ajaxify=\"{$url}\" rel=\"async\">{$this->label}</a>";
    }
}
