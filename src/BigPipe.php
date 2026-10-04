<?php

namespace dobron\BigPipe;

class BigPipe
{
    use JsMods;

    /** @var array<string, Pagelet> */
    protected static array $pagelets = [];
    protected static array $priorities = [];
    protected static array $jsmods = [
        "require" => [],
    ];

    protected function &jsmodsStore(): array
    {
        return self::$jsmods;
    }

    protected function &prioritiesStore(): array
    {
        return self::$priorities;
    }

    public static function addPagelet($id, Pagelet $pagelet): void
    {
        self::$pagelets[$id] = $pagelet;
    }

    public static function jsmods(): array
    {
        array_multisort(static::$priorities, static::$jsmods['require']);

        return static::$jsmods;
    }

    public static function render(): string
    {
        return (string) new static();
    }

    public static function reset(): void
    {
        static::$pagelets = [];
        static::$priorities = [];
        static::$jsmods = [
            "require" => [],
        ];
    }

    public function __toString(): string
    {
        $script = '';

        foreach (static::$pagelets as $i => $pagelet) {
            $data = $pagelet->renderData();

            if (array_key_last(static::$pagelets) === $i) {
                $data['is_last'] = true;
            }

            $script .= "(new (require(\"bigpipe-util/dist/BigPipe\"))).onPageletArrive(" . json_encode($data, JSON_THROW_ON_ERROR) . ");\n";
        }

        $script .= "(new (require(\"bigpipe-util/dist/ServerJS\"))).handle(" . json_encode(static::jsmods(), JSON_THROW_ON_ERROR) . ");";

        static::reset();

        return <<<HTML
<script>
$script
</script>
HTML;

    }
}
