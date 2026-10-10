<?php

namespace App\Pagelets\Tutorial;

use dobron\BigPipe\Pagelet;
use RuntimeException;

/**
 * A pagelet of the tutorials that waits for a slow "API", tells how long the server took to render it, and
 * how long after the page started loading the browser showed it.
 */
class SlowPagelet extends Pagelet
{
    /**
     * The pagelets of the tutorials: a title, the time the "API" takes, the phase and whether it fails.
     */
    public const PAGELETS = [
        'profile' => ['Profile', 0.2, 0, false],
        'feed' => ['Feed', 1.2, 0, false],
        'stats' => ['Statistics', 0.7, 0, false],
        'ads' => ['Ads', 0.5, 0, true],
        'suggestions' => ['Suggestions', 0.3, 1, false],
        'comments' => ['Comments', 0.6, 0, false],
        'related' => ['Related posts', 0.4, 0, false],
    ];

    private string $title;

    private float $delay;

    private bool $fails;

    public function __construct(string $id)
    {
        parent::__construct($id);

        [$this->title, $this->delay, $phase, $this->fails] = self::PAGELETS[$id];

        $this->setPhase($phase);
        $this->setFallback(fn () => view('tutorial._pagelet', [
            'id' => $id,
            'title' => $this->title,
            'failed' => true,
            'phase' => $phase,
            'delay' => $this->delay,
            'renderedAt' => self::elapsed(),
        ])->render());
    }

    protected function content(): string
    {
        // A slow API: in a page rendered in parallel, the other pagelets are rendered meanwhile.
        Pagelet::sleep($this->delay);

        if ($this->fails) {
            throw new RuntimeException("The {$this->title} API did not answer.");
        }

        // Runs when the browser shows the pagelet.
        $this->call('tutorial/Arrival', 'mark', [$this->getId()]);

        return view('tutorial._pagelet', [
            'id' => $this->getId(),
            'title' => $this->title,
            'failed' => false,
            'phase' => $this->getPhase(),
            'delay' => $this->delay,
            'renderedAt' => self::elapsed(),
        ])->render();
    }

    /**
     * Milliseconds since the request started.
     */
    public static function elapsed(): int
    {
        return (int) round((microtime(true) - (defined('LARAVEL_START') ? LARAVEL_START : $_SERVER['REQUEST_TIME_FLOAT'])) * 1000);
    }
}
