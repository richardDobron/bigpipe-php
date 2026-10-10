<?php

namespace dobron\BigPipe;

/**
 * Names the static files of the application and the JavaScript modules the browser loads only
 * when the server calls them. Set them once, e.g. from the manifest of the bundler. A pagelet or
 * a response sends the part of the resource map and the bootloadable modules it uses.
 */
class Bootloader
{
    public const MODULE = 'bigpipe-util/dist/Bootloader';

    /** @var array<string, array{type: string, src: string}> */
    protected static array $resourceMap = [];

    /** @var array<string, list<string>> */
    protected static array $bootloadable = [];

    /**
     * Adds resources by name, e.g. `['feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b.css']]`.
     *
     * @param array<string, array{type: string, src: string}> $resourceMap
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public static function setResourceMap(array $resourceMap): void
    {
        foreach ($resourceMap as $name => $resource) {
            if (!in_array($resource['type'] ?? null, ['css', 'js'], true) || !is_string($resource['src'] ?? null)) {
                throw new Exceptions\BigPipeInvalidArgumentException(
                    "The resource \"$name\" needs a type (css or js) and a src."
                );
            }

            static::$resourceMap[$name] = ['type' => $resource['type'], 'src' => $resource['src']];
        }
    }

    /**
     * Adds JavaScript modules that the browser loads with their resources (names from the resource
     * map or URLs) when the server calls them, e.g. `['Editor' => ['editor.css', 'editor.js']]`.
     *
     * @param array<string, list<string>> $modules
     */
    public static function enableBootload(array $modules): void
    {
        foreach ($modules as $module => $resources) {
            static::$bootloadable[$module] = array_values($resources);
        }
    }

    public static function reset(): void
    {
        static::$resourceMap = [];
        static::$bootloadable = [];
    }

    /**
     * @internal the resource map and the bootloadable modules that the resources and the module
     *           calls need, without empty keys
     *
     * @param list<string> $resources
     * @param array $jsmods
     * @return array{resource_map?: array, bootloadable?: array}
     */
    public static function dataFor(array $resources, array $jsmods): array
    {
        $bootloadable = [];

        foreach ($jsmods['require'] ?? [] as $require) {
            $module = $require[0] ?? null;

            if (is_string($module) && isset(static::$bootloadable[$module])) {
                $bootloadable[$module] = static::$bootloadable[$module];
                array_push($resources, ...static::$bootloadable[$module]);
            }
        }

        $resourceMap = array_intersect_key(static::$resourceMap, array_flip(array_unique($resources)));
        $data = [];

        if (!empty($resourceMap)) {
            $data['resource_map'] = $resourceMap;
        }

        if (!empty($bootloadable)) {
            $data['bootloadable'] = $bootloadable;
        }

        return $data;
    }
}
