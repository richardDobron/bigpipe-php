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

    protected array $priorities = [];

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->element = generate_unique_node_id();

        BigPipe::addPagelet($id, $this);
    }

    public function jsmods(): array
    {
        array_multisort($this->priorities, $this->jsmods['require']);

        return $this->jsmods;
    }

    public function appendContent(string $stringOrFile, bool $isFile = false): static
    {
        if ($isFile) {
            ob_start();
            require $stringOrFile;
            $this->content .= ob_get_contents();
            ob_end_clean();
        } else {
            $this->content .= $stringOrFile;
        }

        return $this;
    }

    public function addOnload(string $code): static
    {
        $this->onloads[] = $code;

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

    public function renderData(): array
    {
        $domops = [
            [
                'setContent',
                '#' . $this->element,
                false,
                [
                    '__html' => $this->content,
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

        return [
            'id' => $this->id,
            'js' => $this->js,
            'css' => $this->css,
            "domops" => $domops,
            'jsmods' => $this->jsmods(),
        ];
    }

    protected function &jsmodsStore(): array
    {
        return $this->jsmods;
    }

    protected function &prioritiesStore(): array
    {
        return $this->priorities;
    }

    public function render(): string
    {
        return $this;
    }

    public function __toString(): string
    {
        return "<div id=\"" . $this->element . "\"></div>";
    }
}
