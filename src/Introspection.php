<?php
namespace sqd;

use Exception;

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

/**
 * evaluates a condition, like in eval($condition)
 * but in a safer way (as the eval function can raise a fatal error that cannot be catched)
 * @return int 
 * - 1 if condition is true 
 * - 0 if condition is false
 * - -1 if something is wrong with the expression
 */
function eval_condition($condition, $safe = false) {

    $condition = str_replace('"', "'", $condition);
    $final_cmd = "echo (( $condition ) ? 1: 0);";

    // check if php exists in shell_exec
    if ($safe) {
        $php_exists = shell_exec('php -r "echo 123;"');
        if ($php_exists !== '123') {
            $res = shell_exec('php -r "'.$final_cmd.'" 2>&1');
            if ($res !== "1" && $res !== "0") {
                //throw new Exception('INVALID_EXPRESSION :: ' . 'In '.basename(__FILE__).' > eval_condition, for expression :"'.$final_cmd.'" res='.$res);
                return -1;
            } else return intval($res);
        }
    }

    $final_cmd = '$res = ('.$condition.') ? 1: 0;';
    $res = -1;
    try {
        eval($final_cmd);
        return $res;
    } catch (Excpetion $e) {
        // log\error('INVALID_EXPRESSION_EVAL', 'In '.basename(__FILE__).' > eval_condition, for expression:"'.$final_cmd.'" error='.$e->getMessage());
        $res = -1;
    } finally {
        return $res;
    }

}

?>