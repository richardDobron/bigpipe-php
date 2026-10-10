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

    /**
     * Sets several options at once, merged with the ones set before.
     */
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

    /**
     * Whether Escape closes the dialog, true by default.
     */
    public function setKeyboard(bool $enabled): static
    {
        return $this->setOption('keyboard', $enabled);
    }

    /**
     * Whether the dialog fades in and out, false by default.
     */
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

    /**
     * The element that opened the dialog, by its id: the focus returns to it when the dialog is
     * closed, see setRefocus().
     */
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

    /**
     * The HTML of the footer, e.g. its buttons; a button with data-dismiss="modal" closes the dialog.
     */
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

    /**
     * Closes the dialogs that are open, the most recent first: all of them, or the last $limit.
     */
    public function closeDialogs(int $limit = -1): static
    {
        return $this->call(static::DIALOG_MODULE, 'close', [$limit]);
    }

    /**
     * Closes the most recent dialog only, e.g. the confirmation on top of a form in a dialog.
     */
    public function closeDialog(): static
    {
        return $this->call(static::DIALOG_MODULE, 'closeCurrent');
    }

    /**
     * Opens the dialog in the browser: the whole content of setDialog(), or the title, body and
     * footer, with the options set before and $options.
     */
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
