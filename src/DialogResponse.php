<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

class DialogResponse extends AsyncResponse
{
    protected $controller = null;
    protected $title = null;
    protected $body = null;
    protected $content = null;
    protected $footer = null;

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function setDialog(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function setFooter(?string $footer): self
    {
        $this->footer = $footer;

        return $this;
    }

    public function setController($fragment): self
    {
        if (!BigPipe::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid fragment.");
        }

        $require = BigPipe::parseRequireCall($fragment);

        $this->controller = $require['module'];

        return $this;
    }

    public function closeDialogs()
    {
        $this->bigPipe()->require("require('bigpipe-util/src/core/Dialog').close()");

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
                    ])
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
                ]
            );
        }

        return $this;
    }
}
