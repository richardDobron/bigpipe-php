<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

class BigPipe
{
    private const JAVASCRIPT_REQUIRE_REGEX = "/^require\(['\"\[]+(?<module>.+?)['\"\]]+\)(\.(?<method>\w+)\(\))?$/";

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
     * @param string|array $fragment
     * @param array $args
     * @return $this
     * @throws BigPipeInvalidArgumentException
     */
    public function require($fragment, array $args = []): self
    {
        if (!self::isValidRequireCall($fragment)) {
            throw new BigPipeInvalidArgumentException("Invalid call.");
        }

        $fragmentParts = self::parseRequireCall($fragment);

        $require = [
            $fragmentParts['module'],
            $fragmentParts['method'] ?? null,
        ];

        if (!empty($args)) {
            static::$jsmods[__FUNCTION__][] = [];
            $lastIndex = count(static::$jsmods[__FUNCTION__]) - 1;

            $require[] = self::transformObjectString($args);
            static::$jsmods[__FUNCTION__][$lastIndex] = array_trim($require);
        } else {
            static::$jsmods[__FUNCTION__][] = array_trim($require);
        }

        return $this;
    }

    public static function jsmods(): array
    {
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
