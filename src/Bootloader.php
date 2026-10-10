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

    /** @var array<string, array{resources: list<string>, priority: int}> */
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
     * With a priority above 0, every page preloads their resources while the network is idle,
     * higher priorities first.
     *
     * @param array<string, list<string>> $modules
     */
    public static function enableBootload(array $modules, int $priority = 0): void
    {
        foreach ($modules as $module => $resources) {
            static::$bootloadable[$module] = ['resources' => array_values($resources), 'priority' => $priority];
        }
    }

    /**
     * Preloads the resources of bootloadable modules on the page right away, e.g. of a dialog the
     * user is likely to open.
     *
     * @throws \Throwable
     */
    public static function preloadModules(string ...$modules): void
    {
        BigPipe::page()->call(static::MODULE, 'preloadModules', [$modules]);
    }

    /**
     * The URL of a resource: its src in the resource map, or the name itself.
     */
    public static function src(string $name): string
    {
        return static::$resourceMap[$name]['src'] ?? $name;
    }

    public static function reset(): void
    {
        static::$resourceMap = [];
        static::$bootloadable = [];
    }

    /**
     * @internal the resource map and the bootloadable modules that the resources and the module
     *           calls need, and with `$preload` the modules to preload, without empty keys
     *
     * @param list<string> $resources
     * @param array $jsmods
     * @return array{resource_map?: array, bootloadable?: array}
     */
    public static function dataFor(array $resources, array $jsmods, bool $preload = false): array
    {
        $modules = [];

        foreach ($jsmods['require'] ?? [] as $require) {
            $module = $require[0] ?? null;

            if ($module === static::MODULE && ($require[1] ?? null) === 'preloadModules') {
                array_push($modules, ...array_values($require[2][0] ?? []));
            } elseif (is_string($module)) {
                $modules[] = $module;
            }
        }

        if ($preload) {
            foreach (static::$bootloadable as $module => $entry) {
                if ($entry['priority'] > 0) {
                    $modules[] = $module;
                }
            }
        }

        $bootloadable = [];

        foreach (array_unique($modules) as $module) {
            if (!isset(static::$bootloadable[$module])) {
                continue;
            }

            $entry = static::$bootloadable[$module];
            $bootloadable[$module] = $entry['priority'] > 0 ? $entry : $entry['resources'];
            array_push($resources, ...$entry['resources']);
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
