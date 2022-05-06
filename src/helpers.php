<?php

namespace dobron\BigPipe;

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
