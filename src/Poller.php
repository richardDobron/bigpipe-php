<?php

namespace dobron\BigPipe;

/**
 * Starts a Poller in the browser, which requests the URL again and again, e.g. to check for new
 * notifications. The endpoint answers with an AsyncResponse, and can control the poller with its
 * id, see requestedId():
 *
 *     (new Poller('/notifications/poll', 30000))->setData(['since' => $since])->start();
 *
 *     $response->call(Poller::requestedId(), 'setInterval', [60000]);
 */
class Poller
{
    public const MODULE = 'bigpipe-util/dist/Poller';

    /** The request parameter with the id of the poller that sent the request. */
    public const PARAM = '__poller';

    protected string $id;
    protected string $method = 'GET';
    protected array $data = [];
    protected ?int $maxRequests = null;
    protected bool $muteWhenHidden = true;
    protected int $muteWhenIdle = 0;
    protected bool $clearOnQuicklingEvents = true;

    /**
     * @param int $interval milliseconds between the end of a request and the next one, at least 2000
     */
    public function __construct(protected string $uri, protected int $interval)
    {
        $this->id = 'poller_' . generate_unique_node_id();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The HTTP method of the requests, GET by default.
     */
    public function setMethod(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Data sent with every request, e.g. the id of the last notification seen.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Stops the poller after this many requests.
     */
    public function setMaxRequests(?int $maxRequests): static
    {
        $this->maxRequests = $maxRequests;

        return $this;
    }

    /**
     * Pauses the poller while the page is hidden, e.g. in a background tab. On by default.
     */
    public function setMuteWhenHidden(bool $muteWhenHidden): static
    {
        $this->muteWhenHidden = $muteWhenHidden;

        return $this;
    }

    /**
     * Pauses the poller when the user was not active for this many milliseconds, 0 to never pause.
     */
    public function setMuteWhenIdle(int $milliseconds): static
    {
        $this->muteWhenIdle = $milliseconds;

        return $this;
    }

    /**
     * Keeps the poller going after a page transition, see Quickling. A page transition ends it by
     * default.
     */
    public function setClearOnQuicklingEvents(bool $clearOnQuicklingEvents): static
    {
        $this->clearOnQuicklingEvents = $clearOnQuicklingEvents;

        return $this;
    }

    /**
     * Starts the poller with the page, or with the pagelet whose content is being rendered.
     *
     * @throws \Throwable
     */
    public function start(): static
    {
        (Pagelet::current() ?? new BigPipe())->call(static::MODULE, null, [$this->options()]);

        return $this;
    }

    /**
     * The id of the poller that sent the current request, to control it from the response, e.g.
     * `$response->call(Poller::requestedId(), 'stop')`.
     */
    public static function requestedId(): ?string
    {
        $id = $_REQUEST[static::PARAM] ?? null;

        return is_string($id) && preg_match('/^poller_[\w-]+$/', $id) ? $id : null;
    }

    protected function options(): array
    {
        $options = [
            'id' => $this->id,
            'uri' => $this->uri,
            'method' => $this->method,
            'interval' => $this->interval,
            'muteWhenHidden' => $this->muteWhenHidden,
            'clearOnQuicklingEvents' => $this->clearOnQuicklingEvents,
        ];

        if (!empty($this->data)) {
            $options['data'] = $this->data;
        }

        if ($this->maxRequests !== null) {
            $options['maxRequests'] = $this->maxRequests;
        }

        if ($this->muteWhenIdle > 0) {
            $options['muteWhenIdle'] = $this->muteWhenIdle;
        }

        return $options;
    }
}
