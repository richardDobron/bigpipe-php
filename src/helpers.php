<?php

namespace dobron\BigPipe;

function array_trim(array $array): array
{
    while (empty(end($array))) {
        array_pop($array);
    }

    return $array;
}
