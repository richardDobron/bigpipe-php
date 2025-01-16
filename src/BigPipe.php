<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

class BigPipe
{
    private const JAVASCRIPT_REQUIRE_REGEX = "/^require\(['\"\[]+(?<module>.+?)['\"\]]+\)(\.(?<method>\w+)\(\))?$/";

    private static array $priorities = [];
    private static array $jsmods = [
        "require" => [],
    ];

    /**
     * Check if require call is valid
     *
     * @param string|array $fragment
     *
     * @return bool
     */
    public static function isValidRequireCall(string|array $fragment): bool
    {
        if (is_array($fragment)) {
            $fragments = count($fragment);
            return $fragments === 1 || $fragments === 2;
        }

        return !!preg_match(static::JAVASCRIPT_REQUIRE_REGEX, $fragment);
    }

    /**
     * Parse JavaScript fragment or array like [module, method]
     *
     * @param string|array $fragment
     *
     * @return array|null
     */
    public static function parseRequireCall(string|array $fragment): ?array
    {
        if (is_array($fragment)) {
            return [
                "module" => $fragment[0],
                "method" => $fragment[1] ?? null,
            ];
        }

        preg_match(static::JAVASCRIPT_REQUIRE_REGEX, $fragment, $match);

        return [
            "module" => $match['module'] ?? null,
            "method" => $match['method'] ?? null,
        ];
    }

    /**
     * @param string|array $fragment
     * @param array $args
     * @return $this
     * @throws BigPipeInvalidArgumentException
     * @throws \Throwable
     */
    public function require(string|array $fragment, array $args = [], int $priority = null): static
    {
        if (!static::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid call.");
        }

        $fragmentParts = static::parseRequireCall($fragment);
        $priorities = static::$priorities;
        $requires = static::$jsmods['require'];

        $require = [
            $fragmentParts['module'],
            $fragmentParts['method'] ?? null,
        ];

        try {
            static::$jsmods[__FUNCTION__][] = [];
            $lastIndex = array_key_last(static::$jsmods[__FUNCTION__]);
            static::$priorities[] = $priority ?? $lastIndex;

            if (! empty($args)) {
                $transformedArgs = static::transformObjectString($args);
                $require[] = $transformedArgs;
            }

            static::$jsmods[__FUNCTION__][$lastIndex] = array_trim($require ?? []);
        } catch (\Throwable $exception) {
            static::$jsmods[__FUNCTION__] = $requires;
            static::$priorities = $priorities;

            throw $exception;
        }

        return $this;
    }

    public static function jsmods(): array
    {
        array_multisort(static::$priorities, static::$jsmods['require']);

        return static::$jsmods;
    }

    private static function transformObjectString(mixed $data)
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
