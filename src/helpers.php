<?php

namespace {

    if (!function_exists('array_key_last')) {
        /**
         * Gets the last key of an array
         *
         * @param mixed $arr An array.
         * @return mixed Returns the last key of array if the array is not empty; NULL otherwise.
         */
        function array_key_last($arr)
        {
            if (!empty($arr)) {
                return key(array_slice($arr, -1, 1, true));
            }

            return null;
        }
    }

}

namespace dobron\BigPipe {

    function array_trim(array $array): array
    {
        while (empty(end($array))) {
            array_pop($array);
        }

        return $array;
    }

    /**
     * Generate a node ID which is guaranteed to be unique for the current page,
     * even across Ajax requests. You should use this method to generate IDs for
     * nodes which require a uniqueness guarantee.
     *
     * @return string A string appropriate for use as an 'id' attribute on a DOM
     *                node. It is guaranteed to be unique for the current page, even
     *                if the current request is a subsequent Ajax request.
     */
    function generate_unique_node_id(): string
    {
        static $unique = 0;

        return implode('_', [
            'u',
            intval($_REQUEST['__req'] ?? 0),
            $unique++,
        ]);
    }

}
