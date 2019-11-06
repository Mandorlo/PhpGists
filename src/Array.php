<?php
namespace sqd;

/**
 * Equivalent of array merge but fixes a bad behavior when dealing with integer keys
 * example : array_merge(['1'=>'a','2'=>'b'], ['2'=>'c','3'=>'d','4'=>'e']) = ['a','b','c','d','e']
 * but array_assign(['1'=>'a','2'=>'b'], ['2'=>'c','3'=>'d','4'=>'e']) = ['1'=>'a', '2'=>'c', '3'=>'d', '4'=>'e']
 * 
 * @param array $arr1 
 * @param array $arr2
 * 
 * @return array merge of $arr1 and $arr2 (keys in $arr2 overwrite keys in $arr1)
 */
function array_assign($arr1, $arr2) {
    foreach ($arr2 as $k => $v) {
        $arr1[(string)$k] = $v;
    }
    return $arr1;
}

/**
 * Builds an associative Array from keys and values
 * 
 * If there are more keys than values, the additional keys are mapped to the null value
 * 
 * @param array|any $keys the list of keys (string). If this is not an array, this is converted into an array
 * @param array|any $values the list of values (any type). If this is not an array, this is converted into an array
 * 
 * @return array the associative array built from keys and values
 */
function array_build($keys, $values) {

    if (!is_array($keys)) $keys = [$keys];
    if (!is_array($values)) $values = [$values];

    $arr = array();
    for ($i = 0; $i < count($keys); $i++) {
        //if (gettype($keys[$i]) != 'string') $keys[$i] = (string) $keys[$i];
        $arr[(string)$keys[$i]] = ($i < count($values)) ? $values[$i] : null;
    }
    return $arr;
}

/**
 * Tells if array has at least one string key
 * 
 * @param array $arr an array
 
 * @return bool true if $arr has ata least one string key
 */
function array_has_string_key($arr) {

    if (!is_array($arr)) return false;
    return count(array_filter(array_keys($arr), 'is_string')) > 0;
}

/**
 * @return the first element in $arr that is not falsy
 * @return $default if all elements are falsy
 */
function first_not_falsy($arr, $default = false) {
    foreach ($arr as $k => $v) {
        if ($v) return $v;
    }
    return $default;
}


/**
 * Retrieves the element in $obj (associative array) following the path
 * 
 * @param array $obj the source array
 * @param string|array $path the path in $obj
 *              $path can contain some special modifiers like "@keys" wich returns the keys of the element
 *              $path can contain a special "*" element which maps the rest of the path to all the elements of the current obj
 * @param string $delim (optional) the path delimiter (cannot contain "@", throws exception)
 * @param any $return_value_if_wrong (optional) the value to be returned if the path was not found in $obj
 * 
 * @return any the value of the sub-object of $obj at $path. Or $return_value_if_wrong if the path was not found
 * 
 * example 1 :
 * $obj     = {'a': {'b':1, 'c':{'e':5}, 'd': 4}}
 * $path    = "/a/c/e"
 * returns  = 5
 * 
 * example 2 :
 * $obj     = {'a': {'b':1, 'c':{'e':5, 'f':6}, 'd': 4}}
 * $path    = "/a/c@keys"
 * returns  = ['e', 'f']
 * 
 */
function get_obj_path($obj, $path, $delim = '/', $return_value_if_wrong = false) {

    if ( $path == $delim ) return $obj;
    if ( strpos($delim, "@") ) throw new Exception('InvalidPathDelimiter');

    $apply_modifier = function($modifier, $v) {
        // aux function to apply special modifiers on output
        if ($modifier == 'keys' && is_array($v)) {
            // the "keys" modifier returns only the element's keys
            return array_keys($v);
        }
        // ... you can add other modifiers here
        return $v;
    };

    $path_elements = $path;
    // if $path is a string, we split it on $delim
    if (gettype($path) == 'string') {
        if (strlen($path) == 0) return $return_value_if_wrong;
        if (substr($path, 0, strlen($delim)) == $delim || substr($path, strlen($path)-strlen($delim)) == $delim) $path = trim($path, $delim);
        if (strlen($path) == 0) return $return_value_if_wrong;

        $path_elements = explode($delim, $path);
    }

    // now we have $path_elements, the array of $path's elements
    if (count($path_elements) == 0) return $obj;
    $rest = array_slice($path_elements, 1);

    // if we have an "@" modifier, we apply it
    if (strpos($path_elements[0], "@") !== false) {
        // we split the first path element with "@" in case there is a modifier to apply
        $path_el_arr = explode('@', $path_elements[0]);
        $path_el_name = $path_el_arr[0];
        $new_obj = (isset( $obj[$path_el_name] )) ? $obj[$path_el_name] : $obj;
        $new_obj = $apply_modifier($path_el_arr[1], $new_obj);
        return get_obj_path($new_obj, $rest, $delim, $return_value_if_wrong);

    // if we have a special "*" in the path, it means we apply the rest of the path to each element of the current $obj
    // if $obj is a dict-like array, this loops only through the $obj values but keeps its dict-like structure
    } else if ($path_elements[0] == '*') {
        if (!is_array($obj)) return get_obj_path($obj, $rest, $delim, $return_value_if_wrong);
        return array_map(
            function($el) use ($rest, $delim, $return_value_if_wrong) {
                return get_obj_path($el, $rest, $delim, $return_value_if_wrong);
            }, 
        $obj);

    // if it's a normal element in path
    } else {
        if (isset($obj[$path_elements[0]])) return get_obj_path($obj[$path_elements[0]], $rest, $delim, $return_value_if_wrong);
        return $return_value_if_wrong;
    }
}

/**
 * implodes/joins an associative array in a string
 * 
 * @param array $arr the array to implode
 * @param string $glue_keyval (optional default to '=') the glue to glue key, value pairs
 * @param string $glue_elements (optional defautl ot ',') the glue to glue the different elements
 * 
 * @return string the imploded array
 * 
 * example :
 * $arr = [a:1, b:2, c:'coco']
 * return -> "a=1,b=2,c=coco"
 */
function implode_assoc($arr, $glue_keyval = '=', $glue_elements = ',') {

    $new_arr = array();
    foreach ($arr as $key => $val) {
        if (gettype($val) != 'string') $val = json_encode($val);
        $new_arr[] = $key . $glue_keyval . $val;
    }
    return implode($glue_elements, $new_arr);
}

/**
 * Reverse keys and values from the mapper (almost equivalent of array_flip)
 * 
 * Special case :
 * $m = {a: [1, 2], b: [3, 4], c: 5}
 * RETURNS --> {1: 'a', 2: 'a', 3: 'b', 4: 'b', 5: 'c'}
 * 
 * @param array $m the assoc array (called mapper here)
 * @return array the assoc array which $m where keys and values have been exchanged
 */
function mapper_reverse($m) {

    $rev_m = array();
    foreach ($m as $key => $val) {
        if (is_array($val)) {
            $new_arr = array_build($val, array_fill(0, count($val), $key));
            $rev_m = (count($rev_m) > 0) ? array_assign($rev_m, $new_arr) : $new_arr;
        } else if (gettype($val) == 'string' || gettype($val) == 'integer') {
            $rev_m[$val] = $key;
        }
    }
    return $rev_m;
}


?>