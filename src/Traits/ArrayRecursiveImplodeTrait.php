<?php

namespace BFilters\Traits;

/**
 * Trait ArrayRecursiveImplode
 *
 * @package Infrastructure\Traits
 */
trait ArrayRecursiveImplodeTrait
{
    /**
     * Recursively implodes an array with optional key inclusion
     *
     * Example of $includeKeys output: key, value, key, value, key, value
     *
     * @param array  $data        multi-dimensional array to recursively implode
     * @param string $glue        value that glues elements together
     * @param bool   $includeKeys include keys before their values
     * @param bool   $trimAll     trim ALL whitespace from string
     *
     * @return  string  imploded array
     */
    protected function arrayRecursiveImplode(
        array $data,
        string $glue = ',',
        bool $includeKeys = false,
        bool $trimAll = true
    ): string {
        $gluedString = '';

        // Recursively iterates array and adds key/value to glued string
        array_walk_recursive($data, function ($value, $key) use ($glue, $includeKeys, &$gluedString) {
            $includeKeys and $gluedString .= $key . $glue;
            $gluedString .= $value . $glue;
        });

        // Removes last $glue from string
        strlen($glue) > 0 and $gluedString = substr($gluedString, 0, (-1) * strlen($glue));

        // Trim ALL whitespace
        $gluedString = trim($gluedString);

        return (string)$gluedString;
    }
}
