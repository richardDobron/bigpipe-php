<?php

namespace dobron\BigPipe;

/**
 * Values that JSON can't express, replaced in the browser before a module gets them:
 *
 *     $response->call('Chart', 'render', [TransportMarker::element('chart'), TransportMarker::map($data)]);
 */
class TransportMarker
{
    public const TRANSPORT_HTML = "__html";
    public const TRANSPORT_CONTAINER = "__container";
    public const TRANSPORT_MODULE = "__m";
    public const TRANSPORT_ELEMENT = "__e";
    public const TRANSPORT_MAP = "__map";
    public const TRANSPORT_SET = "__set";
    public const TRANSPORT_RELATIVE = "__rel";
    /** The module the server chose for another one, see JsMods::defineCond(). */
    public const TRANSPORT_CONDITION = "__rc";

    /**
     * HTML content of a DOM operation.
     *
     * @return array<string, mixed>
     */
    public static function html(?string $content): array
    {
        return [self::TRANSPORT_HTML => $content];
    }

    /**
     * The element with this id.
     *
     * @return array<string, mixed>
     */
    public static function element(string $elementId): array
    {
        return [self::TRANSPORT_ELEMENT => $elementId];
    }

    /**
     * The module, resolved like a module of require().
     *
     * @return array<string, mixed>
     */
    public static function module(string $module): array
    {
        return [self::TRANSPORT_MODULE => $module];
    }

    /**
     * The element that sent the request, e.g. the link or form of the Primer, or null for a page.
     *
     * @return array<string, mixed>
     */
    public static function relative(): array
    {
        return [self::TRANSPORT_RELATIVE => 1];
    }

    /**
     * A Map created from the list of [key, value].
     *
     * @return array<string, mixed>
     */
    public static function map(array $data): array
    {
        return [self::TRANSPORT_MAP => $data];
    }

    /**
     * A Set created from the list of values.
     *
     * @return array<string, mixed>
     */
    public static function set(array $data): array
    {
        return [self::TRANSPORT_SET => $data];
    }

    /**
     * @deprecated use html()
     */
    public static function transportHtml(?string $content): array
    {
        return static::html($content);
    }

    /**
     * @deprecated use element()
     */
    public static function transportElement(string $elementId): array
    {
        return static::element($elementId);
    }

    /**
     * @deprecated use module()
     */
    public static function transportModule(string $module): array
    {
        return static::module($module);
    }

    /**
     * @deprecated use map()
     */
    public static function transportMap(array $data): array
    {
        return static::map($data);
    }

    /**
     * @deprecated use set()
     */
    public static function transportSet(array $data): array
    {
        return static::set($data);
    }
}
