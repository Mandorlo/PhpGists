<?php
namespace sqd;

/**
 * In this module we provide useful functions for trees
 * trees is just another name for dicts of dicts in Python or assoc arrays of assoc arrays in PHP
 */

function tree_implode($tree_dict, $start_level, $end_level, $glue = "_") {
    $newt = [];
    if (!is_array($tree_dict)) return $tree_dict;
    if ($start_level >= $end_level) return $tree_dict;
    if ($start_level > 0) {
        foreach ($tree_dict as $k1 => $v1) {
            $newt[$k1] = tree_implode($v1, $start_level-1, $end_level-1, $glue);
        }
        return $newt;
    }
    if ($end_level > 1) {
        $newt = $tree_dict;
        for ($i = 0; $i < $end_level - $start_level; $i++) {
            $newt = tree_implode($newt, 0, 1, $glue);
        }
        return $newt;
    }

    foreach ($tree_dict as $k1 => $v1) {
        if (is_array($v1) && empty($v1)) {
            $newt[$k1.$glue] = null;
        } else if (is_array($v1)) {
            foreach ($v1 as $k2 => $v2) {
                $newt[$k1.$glue.$k2] = $v2;
            }
        } else {
            $newt[$k1.$glue.$v1] = null;
        }
    }   
    return $newt;
}

?>