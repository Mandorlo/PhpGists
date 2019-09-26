<?php
namespace ccn;

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
 * $path    = "/a/b/c@keys"
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

?>