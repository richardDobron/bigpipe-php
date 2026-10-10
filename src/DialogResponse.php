<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

class DialogResponse extends AsyncResponse
{
    public const DIALOG_MODULE = 'bigpipe-util/dist/core/Dialog';

    protected ?string $controller = null;
    protected array $controllerArgs = [];
    protected ?string $title = null;
    protected mixed $body = null;
    protected mixed $content = null;
    protected ?string $footer = null;
    protected array $options = [];

    /**
     * Sets an option of the dialog by its name in the browser part, see the other setters.
     */
    public function setOption(string $name, mixed $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function setOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * true, false or 'static' for a backdrop that doesn't close the dialog.
     */
    public function setBackdrop(bool|string $backdrop): static
    {
        return $this->setOption('backdrop', $backdrop);
    }

    public function setKeyboard(bool $enabled): static
    {
        return $this->setOption('keyboard', $enabled);
    }

    public function setAnimate(bool $enabled): static
    {
        return $this->setOption('animate', $enabled);
    }

    /**
     * Shows the dialog after the delay in milliseconds.
     */
    public function setTimeout(int $milliseconds): static
    {
        return $this->setOption('timeout', $milliseconds);
    }

    /**
     * Moves the focus into the dialog when it is shown.
     */
    public function setAutoFocus(bool $enabled = true): static
    {
        return $this->setOption('autoFocus', $enabled);
    }

    /**
     * Keeps Tab inside of the dialog.
     */
    public function setTrapFocus(bool $enabled = true): static
    {
        return $this->setOption('trapFocus', $enabled);
    }

    /**
     * Gives the focus back to the element that opened the dialog, or the one given to
     * setCausalElement(), when the dialog is closed.
     */
    public function setRefocus(bool $enabled = true): static
    {
        return $this->setOption('refocus', $enabled);
    }

    public function setCausalElement(string $elementId): static
    {
        return $this->setOption('causalElement', TransportMarker::element($elementId));
    }

    /**
     * Closes the dialog before a page transition.
     */
    public function setHideOnTransition(bool $enabled = true): static
    {
        return $this->setOption('hideOnTransition', $enabled);
    }

    /**
     * Closes the dialog when a request sent from inside of it succeeds: from any element, or only
     * from the elements that match the selector.
     */
    public function setHideOnSuccess(bool|string $selector = true): static
    {
        return $this->setOption('hideOnSuccess', $selector);
    }

    /**
     * Sets the top margin of the dialog: a fixed one in pixels, or else a part of the free height
     * of the window, half of it when centered.
     */
    public function setPosition(
        ?int $top = null,
        bool $centered = false,
        bool $ignoreTopInShortViewport = false
    ): static {
        return $this->setOption('position', array_filter([
            'top' => $top,
            'centered' => $centered,
            'ignoreTopInShortViewport' => $ignoreTopInShortViewport,
        ], static fn ($value) => $value !== null && $value !== false) ?: true);
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param null|string|object $body
     * @return static
     */
    public function setBody(mixed $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param null|string|object $content
     * @return static
     */
    public function setDialog(mixed $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function setFooter(?string $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * The module created with the dialog and the arguments, e.g. setController('PostEditor', [$post]).
     * The module can also be written as "require('PostEditor')" or ['PostEditor'].
     *
     * @param array{0: string, 1?: string}|string $fragment
     * @param array $args
     * @return static
     * @throws BigPipeInvalidArgumentException
     */
    public function setController(string|array $fragment, array $args = []): static
    {
        if (is_string($fragment) && preg_match('/^[\w\/.@-]+$/', $fragment)) {
            $fragment = [$fragment];
        }

        if (!BigPipe::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid fragment.");
        }

        $require = BigPipe::parseRequireCall($fragment);

        if (!empty($require['method'])) {
            throw new BigPipeInvalidArgumentException(
                "Dialog controller can't have a method, use require('{$require['module']}') instead."
            );
        }

        $this->controller = $require['module'];
        $this->controllerArgs = $args;

        return $this;
    }

    public function closeDialogs(int $limit = -1): static
    {
        return $this->call(static::DIALOG_MODULE, 'close', [$limit]);
    }

    public function closeDialog(): static
    {
        return $this->call(static::DIALOG_MODULE, 'closeCurrent');
    }

    public function dialog(array $options = []): static
    {
        $options = array_merge($this->options, $options);

        if ($this->content) {
            $this->call(
                static::DIALOG_MODULE,
                'render',
                [
                    array_merge($options, [
                        'content' => $this->content,
                        'controller' => $this->controller,
                    ]),
                    $this->controllerArgs,
                ]
            );
        } else {
            $options = array_merge($options, [
                'title' => $this->title,
                'body' => $this->body,
                'footer' => $this->footer,
                'controller' => $this->controller,
            ]);

            $this->call(
                static::DIALOG_MODULE,
                'showFromModel',
                [
                    $options,
                    $this->controllerArgs,
                ]
            );
        }

        return $this;
    }
}
