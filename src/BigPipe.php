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
     * @param string $fragment
     *
     * @return bool
     */
    public static function isValidRequireCall(string $fragment): bool
    {
        return !!preg_match(self::JAVASCRIPT_REQUIRE_REGEX, $fragment);
    }

    /**
     * Parse JavaScript fragment
     *
     * @param string $fragment
     * @param bool $multiple
     *
     * @return array|null
     */
    public static function parseRequireCall(string $fragment, bool $multiple = false): ?array
    {
        preg_match(self::JAVASCRIPT_REQUIRE_REGEX, $fragment, $match);

        if ($multiple) {
            return preg_split("/',\s+'/", $match['module']);
        }

        return [
            "module" => $match['module'] ?? null,
            "method" => $match['method'] ?? null,
        ];
    }

    /**
     * @param string $fragment
     * @param array $args
     * @return $this
     * @throws BigPipeInvalidArgumentException
     */
    public function require(string $fragment, array $args = []): self
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
