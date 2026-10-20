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

    /** @var array<string, array{type: string, src: string, module?: true}> */
    protected static array $resourceMap = [];

    /** @var array<string, list<string>> */
    protected static array $requires = [];

    /** @var array<string, array{resources: list<string>, priority: int}> */
    protected static array $bootloadable = [];

    /**
     * Adds resources by name, e.g. `['feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b.css']]`, with
     * `'module' => true` for a script that is an ES module and `'requires' => [...]` for the resources it needs.
     *
     * @param array<string, array{type: string, src: string, module?: bool, requires?: list<string>}> $resourceMap
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

            $requires = $resource['requires'] ?? [];

            if (!is_array($requires) || array_filter($requires, fn ($name) => !is_string($name))) {
                throw new Exceptions\BigPipeInvalidArgumentException(
                    "The requires of the resource \"$name\" must be a list of names."
                );
            }

            if (!empty($requires)) {
                static::$requires[$name] = array_values($requires);
            } else {
                unset(static::$requires[$name]);
            }

            // A script that is an ES module, e.g. a chunk of Vite, is loaded as <script type="module">.
            static::$resourceMap[$name] = ['type' => $resource['type'], 'src' => $resource['src']]
                + ($resource['type'] === 'js' && ($resource['module'] ?? false) === true ? ['module' => true] : []);
        }
    }

    /**
     * Adds the scripts and stylesheets of the manifest of Vite (`build.manifest`), named by their
     * key in the manifest, e.g. `resources/js/Editor.js`, and a stylesheet of a chunk by its file.
     * A script requires its imports and stylesheets. The files are served from `$baseUrl`.
     *
     * @throws Exceptions\BigPipeInvalidArgumentException
     */
    public static function fromViteManifest(string $path, string $baseUrl = '/build/'): void
    {
        $manifest = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (!is_array($manifest)) {
            throw new Exceptions\BigPipeInvalidArgumentException("The Vite manifest \"$path\" is not readable JSON.");
        }

        $baseUrl = rtrim($baseUrl, '/') . '/';
        $resourceMap = [];

        foreach ($manifest as $name => $chunk) {
            $type = static::viteType($chunk['file'] ?? null);

            if ($type === null) {
                continue;
            }

            $resource = ['type' => $type, 'src' => $baseUrl . $chunk['file']];

            if ($type === 'js') {
                $resource['module'] = true;
                $requires = [];

                foreach ($chunk['imports'] ?? [] as $import) {
                    if (static::viteType($manifest[$import]['file'] ?? null) !== null) {
                        $requires[] = $import;
                    }
                }

                foreach ($chunk['css'] ?? [] as $file) {
                    $resourceMap[$file] ??= ['type' => 'css', 'src' => $baseUrl . $file];
                    $requires[] = $file;
                }

                if (!empty($requires)) {
                    $resource['requires'] = $requires;
                }
            }

            $resourceMap[$name] = $resource;
        }

        static::setResourceMap($resourceMap);
    }

    private static function viteType(mixed $file): ?string
    {
        return match (is_string($file) ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : null) {
            'js', 'mjs' => 'js',
            'css' => 'css',
            default => null,
        };
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

    /**
     * The resources with the resources they require, each before the resources that require it and
     * once. With a type, only the resources of that type, and the URLs.
     *
     * @param list<string> $names
     * @return list<string>
     */
    public static function resolve(array $names, ?string $type = null): array
    {
        $resolved = [];
        $visit = function (string $name) use (&$visit, &$resolved): void {
            if (isset($resolved[$name])) {
                return;
            }

            $resolved[$name] = false;

            foreach (static::$requires[$name] ?? [] as $required) {
                $visit($required);
            }

            unset($resolved[$name]);
            $resolved[$name] = true;
        };

        foreach ($names as $name) {
            $visit($name);
        }

        return array_values(array_filter(
            array_map('strval', array_keys($resolved)),
            fn ($name) => $type === null || (static::$resourceMap[$name]['type'] ?? $type) === $type
        ));
    }

    public static function reset(): void
    {
        static::$resourceMap = [];
        static::$requires = [];
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
                continue;
            }

            if (is_string($module)) {
                $modules[] = $module;
            }

            static::collectModules($require[2] ?? [], $modules);
        }

        // The module the server chose for another one, see JsMods::defineCond().
        foreach ($jsmods['define'] ?? [] as $define) {
            $target = $define[1][TransportMarker::TRANSPORT_CONDITION] ?? null;

            if (is_array($define[1] ?? null) && is_string($target)) {
                $modules[] = $target;
            }
        }

        // The browser creates an instance with its class, and loads it first when it is bootloadable.
        foreach ($jsmods['instances'] ?? [] as $instance) {
            if (is_string($instance[1] ?? null)) {
                $modules[] = $instance[1];
            }

            static::collectModules($instance[2] ?? [], $modules);
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
            $entry['resources'] = static::resolve($entry['resources']);
            $bootloadable[$module] = $entry['priority'] > 0 ? $entry : $entry['resources'];
            array_push($resources, ...$entry['resources']);
        }

        $resourceMap = array_intersect_key(static::$resourceMap, array_flip(static::resolve($resources)));
        $data = [];

        if (!empty($resourceMap)) {
            $data['resource_map'] = $resourceMap;
        }

        if (!empty($bootloadable)) {
            $data['bootloadable'] = $bootloadable;
        }

        return $data;
    }

    /**
     * Adds the modules of the module transport markers in the arguments, e.g. of an Instance.
     *
     * @param list<string> $modules
     */
    private static function collectModules(mixed $value, array &$modules): void
    {
        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (!is_array($value)) {
            return;
        }

        if (is_string($value[TransportMarker::TRANSPORT_MODULE] ?? null)) {
            $modules[] = $value[TransportMarker::TRANSPORT_MODULE];

            return;
        }

        foreach ($value as $item) {
            static::collectModules($item, $modules);
        }
    }
}
