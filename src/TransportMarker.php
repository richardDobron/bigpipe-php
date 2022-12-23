<?php

namespace dobron\BigPipe;

class TransportMarker
{
    /**
     * Transport markers
     */
    public const TRANSPORT_HTML = "__html";
    public const TRANSPORT_MODULE = "__m";
    public const TRANSPORT_ELEMENT = "__e";
    public const TRANSPORT_MAP = "__map";
    public const TRANSPORT_SET = "__set";

    /**
     * Create transport marker
     *
     * @param mixed $data
     * @param string $marker
     *
     * @return array
     */
    private static function createTransportMarker($data, string $marker): array
    {
        return [
            $marker => $data
        ];
    }

    /**
     * Create HTML transport marker
     *
     * @param null|string $content
     *
     * @return array
     */
    public static function transportHtml(?string $content): array
    {
        return self::createTransportMarker($content, self::TRANSPORT_HTML);
    }

    /**
     * Create element transport marker
     *
     * @param string $elementId
     *
     * @return array
     */
    public static function transportElement(string $elementId): array
    {
        return self::createTransportMarker($elementId, self::TRANSPORT_ELEMENT);
    }

    /**
     * Create module transport marker
     *
     * @param string $module
     *
     * @return array
     */
    public static function transportModule(string $module): array
    {
        return self::createTransportMarker($module, self::TRANSPORT_MODULE);
    }

    /**
     * Create Map object transport marker
     *
     * @param array $data
     *
     * @return array
     */
    public static function transportMap(array $data): array
    {
        return self::createTransportMarker($data, self::TRANSPORT_MAP);
    }

    /**
     * Create Set objects transport marker
     *
     * @param array $data
     *
     * @return array
     */
    public static function transportSet(array $data): array
    {
        return self::createTransportMarker($data, self::TRANSPORT_SET);
    }
}
