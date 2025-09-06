<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

class BigPipe
{
    private const JAVASCRIPT_REQUIRE_REGEX = "/^require\(['\"\[]+(?<module>.+?)['\"\]]+\)(\.(?<method>\w+)\(\))?$/";

    private static $priorities = [];
    private static $jsmods = [
        "require" => [],
    ];

    /**
     * Check if require call is valid
     *
     * @param string|array $fragment
     *
     * @return bool
     */
    public static function isValidRequireCall($fragment): bool
    {
        if (is_array($fragment)) {
            $fragments = count($fragment);
            return $fragments === 1 || $fragments === 2;
        }

        return !!preg_match(self::JAVASCRIPT_REQUIRE_REGEX, $fragment);
    }

    /**
     * Parse JavaScript fragment or array like [module, method]
     *
     * @param string|array $fragment
     *
     * @return array|null
     */
    public static function parseRequireCall($fragment): ?array
    {
        if (is_array($fragment)) {
            return [
                "module" => $fragment[0],
                "method" => $fragment[1] ?? null,
            ];
        }

        preg_match(self::JAVASCRIPT_REQUIRE_REGEX, $fragment, $match);

        return [
            "module" => $match['module'] ?? null,
            "method" => $match['method'] ?? null,
        ];
    }

    /**
     * @param string|array|null $fragment
     * @param array $args
     * @param int|null $priority
     * @return self|RequireProxy
     * @throws \Throwable
     */
    public function require($fragment = null, array $args = [], int $priority = null)
    {
        if ($fragment === null) {
            return new RequireProxy($this, $priority);
        }

        if (!self::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid call.");
        }

        $fragmentParts = self::parseRequireCall($fragment);
        $priorities = self::$priorities;
        $requires = self::$jsmods['require'];

        $require = [
            $fragmentParts['module'],
            $fragmentParts['method'] ?? null,
        ];

        try {
            static::$jsmods[__FUNCTION__][] = [];
            $lastIndex = array_key_last(static::$jsmods[__FUNCTION__]);
            static::$priorities[] = $priority ?? $lastIndex;

            if (!empty($args)) {
                $transformedArgs = self::transformObjectString($args);
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

    private static function transformObjectString($data)
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
                    $result[$index][$key] = self::transformObjectString($value);
                }
            } else {
                $result[$index] = self::transformObjectString($item);
            }
        }

        return $result;
    }
}
