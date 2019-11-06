<?php
namespace sqd;


/**
 * @return the first non-empty argument
 * @return $return_val if everything is false
 */
function first_of($return_val = false) {
    $arg_list = func_get_args();
    foreach ($arg_list as $arg) {
        if (!empty($arg)) return $arg;
    }
    return $return_val;
}

?>