<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

trait JsMods
{
    protected static string $JAVASCRIPT_REQUIRE_REGEX = "/^require\(['\"\[]+(?<module>.+?)['\"\]]+\)(\.(?<method>\w+)\(\))?$/";

    abstract protected function &jsmodsStore(): array;
    abstract protected function &prioritiesStore(): array;

    /**
     * Check if require call is valid
     *
     * @param string|array{0: string, 1?: string} $fragment
     *
     * @return bool
     */
    public static function isValidRequireCall(string|array $fragment): bool
    {
        if (is_array($fragment)) {
            $fragments = count($fragment);
            return $fragments === 1 || $fragments === 2;
        }

        return !!preg_match(static::$JAVASCRIPT_REQUIRE_REGEX, $fragment);
    }

    /**
     * Parse JavaScript fragment or array like [module, method]
     *
     * @param string|array{0: string, 1?: string} $fragment
     *
     * @return array{module: null|string, method: null|string}
     */
    public static function parseRequireCall(string|array $fragment): array
    {
        if (is_array($fragment)) {
            return [
                "module" => $fragment[0],
                "method" => $fragment[1] ?? null,
            ];
        }

        preg_match(static::$JAVASCRIPT_REQUIRE_REGEX, $fragment, $match);

        return [
            "module" => $match['module'] ?? null,
            "method" => $match['method'] ?? null,
        ];
    }

    /**
     * @param string|array{0: string, 1?: string}|null $fragment
     * @param array $args
     * @param int|null $priority
     * @return static|RequireProxy
     * @throws \Throwable
     */
    public function require(string|array $fragment = null, array $args = [], int $priority = null): RequireProxy|static
    {
        if ($fragment === null) {
            return new RequireProxy($this, $priority);
        }

        if (!static::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid call.");
        }

        $fragmentParts = static::parseRequireCall($fragment);
        $jsmods = &$this->jsmodsStore();
        $priorities = &$this->prioritiesStore();
        $prioritiesBackup = $priorities;
        $requires = $jsmods['require'];

        $require = [
            $fragmentParts['module'],
            $fragmentParts['method'] ?? null,
        ];

        try {
            $jsmods[__FUNCTION__][] = [];
            $lastIndex = array_key_last($jsmods[__FUNCTION__]);
            $priorities[] = $priority ?? $lastIndex;

            if (!empty($args)) {
                $transformedArgs = static::transformObjectString($args);
                $require[] = $transformedArgs;
            }

            $jsmods[__FUNCTION__][$lastIndex] = array_trim($require);
        } catch (\Throwable $exception) {
            $jsmods[__FUNCTION__] = $requires;
            $priorities = $prioritiesBackup;

            throw $exception;
        }

        return $this;
    }

    protected static function transformObjectString(mixed $data): mixed
    {
        if (is_object($data) && method_exists($data, '__toString')) {
            return (string)$data;
        }

        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $index => $item) {
            if (is_array($item)) {
                $result[$index] = [];
                foreach ($item as $key => $value) {
                    $result[$index][$key] = static::transformObjectString($value);
                }
            } else {
                $result[$index] = static::transformObjectString($item);
            }
        }

        return $result;
    }
}
