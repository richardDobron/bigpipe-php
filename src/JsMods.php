<?php

namespace dobron\BigPipe;

use dobron\BigPipe\Exceptions\BigPipeInvalidArgumentException;

trait JsMods
{
    protected static string $JAVASCRIPT_REQUIRE_REGEX = "/^require\(['\"\[]+(?<module>.+?)['\"\]]+\)(\.(?<method>\w+)\(\))?$/";

    abstract protected function &jsmodsStore(): array;
    abstract protected function &prioritiesStore(): array;

    /**
     * @internal
     * @param string|array{0: string, 1?: string} $fragment
     */
    public static function isValidRequireCall(string|array $fragment): bool
    {
        if (is_array($fragment)) {
            $fragments = count($fragment);
            $module = $fragment[0] ?? null;

            return ($fragments === 1 || $fragments === 2) && is_string($module) && $module !== '';
        }

        return !!preg_match(static::$JAVASCRIPT_REQUIRE_REGEX, $fragment);
    }

    /**
     * @internal
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
     * Calls a JavaScript module in the browser: `$module.$method(...$args)`, or without a method
     * `new $module(...$args)` for a class. The modules are called by their priority, then in the
     * order of the calls.
     *
     *     $response->call('Chart', 'render', [TransportMarker::element('chart'), $data]);
     *
     * @throws \Throwable
     */
    public function call(string $module, ?string $method = null, array $args = [], ?int $priority = null): static
    {
        return $this->require($method === null ? [$module] : [$module, $method], $args, $priority);
    }

    /**
     * Calls a JavaScript module, see call(). The fragment is [module, method] or [module]; without
     * one, it returns a RequireProxy: `$response->require()->Chart()->render([$element])`.
     *
     * @deprecated as a string like "require('Module').method()", use call() instead.
     *
     * @param string|array{0: string, 1?: string}|null $fragment
     * @param array $args
     * @param int|null $priority
     * @return static|RequireProxy
     * @throws \Throwable
     */
    public function require(string|array|null $fragment = null, array $args = [], ?int $priority = null): RequireProxy|static
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

    /**
     * Defines an object the browser creates as `new module(...args)` the first time it is used, and
     * shares between all later uses. Call its methods with Instance::call(), or pass it as an
     * argument to another module.
     *
     * @throws \Throwable
     */
    public function instance(string $module, array $args = []): Instance
    {
        if ($module === '') {
            throw new BigPipeInvalidArgumentException("Invalid module.");
        }

        $id = '__inst_' . generate_unique_node_id();
        $instance = [$id, $module];

        if (!empty($args)) {
            $instance[] = static::transformObjectString($args);
        }

        $jsmods = &$this->jsmodsStore();
        $jsmods['instances'][] = $instance;

        return new Instance($this, $id);
    }

    /**
     * Sends data the browser can require as a module, e.g. the configuration of the page.
     * Defining a module again, also in a later response, replaces it.
     *
     * @throws \Throwable
     */
    public function define(string $module, mixed $exports): static
    {
        if ($module === '') {
            throw new BigPipeInvalidArgumentException("Invalid module.");
        }

        $define = [$module, static::transformObjectString($exports)];

        $jsmods = &$this->jsmodsStore();
        $jsmods['define'][] = $define;

        return $this;
    }

    /**
     * Defines a module that is an element of the page: the browser finds the element with the id
     * when the module is first required, so any module can require it by name. The same as
     * define($module, TransportMarker::element($elementId)).
     *
     * @throws BigPipeInvalidArgumentException
     */
    public function defineElement(string $module, string $elementId): static
    {
        if ($module === '' || $elementId === '') {
            throw new BigPipeInvalidArgumentException("Invalid module or element id.");
        }

        return $this->define($module, TransportMarker::element($elementId));
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
