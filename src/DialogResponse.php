<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

class DialogResponse extends AsyncResponse
{
    protected $controller = null;
    protected $controllerArgs = [];
    protected $title = null;
    protected $body = null;
    protected $content = null;
    protected $footer = null;

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param null|string|object $body
     * @return $this
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param null|string|object $content
     * @return $this
     */
    public function setDialog($content): self
    {
        $this->content = $content;

        return $this;
    }

    public function setFooter(?string $footer): self
    {
        $this->footer = $footer;

        return $this;
    }

    public function setController($fragment, array $args = []): self
    {
        if (!BigPipe::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid fragment.");
        }

        $require = BigPipe::parseRequireCall($fragment);

        $this->controller = $require['module'];
        $this->controllerArgs = $args;

        return $this;
    }

    public function closeDialogs()
    {
        $this->bigPipe()->require("require('bigpipe-util/src/core/Dialog').close()");

        return $this;
    }

    public function closeDialog()
    {
        $this->bigPipe()->require("require('bigpipe-util/src/core/Dialog').closeCurrent()");

        return $this;
    }

    public function dialog(array $options = [])
    {
        if ($this->content) {
            $this->bigPipe()->require(
                "require('bigpipe-util/src/core/Dialog').render()",
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

            $this->bigPipe()->require(
                "require('bigpipe-util/src/core/Dialog').showFromModel()",
                [
                    $options,
                    $this->controllerArgs,
                ]
            );
        }

        return $this;
    }
}
