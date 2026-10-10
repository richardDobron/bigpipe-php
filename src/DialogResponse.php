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
