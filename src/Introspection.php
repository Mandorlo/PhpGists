<?php
namespace ccn;

/**
 * returns the name of a callable
 * 
 * @param callable $callable the callable to retrieve the name from
 * 
 * @return string the name of a callable / a function
 * @return false if $callable is not callable
 */
function callable_name($callable) {

    if (!is_callable($callable)) return false;

    if (is_string($callable)) {
        return trim($callable);
    } else if (is_array($callable)) {
        if (is_object($callable[0])) {
            return sprintf("%s::%s", get_class($callable[0]), trim($callable[1]));
        } else {
            return sprintf("%s::%s", trim($callable[0]), trim($callable[1]));
        }
    } else if ($callable instanceof Closure) {
        return 'closure';
    } else {
        return 'unknown';
    }
}

?>