<?php

require_once(__dir__."/String.php");

/**
 * Sorts an array/list of dicts by key
 * 
 * @param array $arr the source array/list of dicts like [{a:5}, {a:2}, {a:1}]
 * @param string $key the key by which we should sort (e.g. 'a')
 * 
 * @return array the $arr array/list sorted by $key
 * 
 */
function lod_sort($arr, $key) {

    $b = usort($arr, function($a, $b) use ($key) {
        if (!isset($a[$key])) return 1;
        if (!isset($b[$key])) return -1;
        if (gettype($a[$key]) == 'string') $a[$key] = strtolower(remove_accents($a[$key]));
        if (gettype($b[$key]) == 'string') $b[$key] = strtolower(remove_accents($b[$key]));
        if ($a[$key] < $b[$key]) return -1;
        return 1;
    });
    return $arr;    
}

?>