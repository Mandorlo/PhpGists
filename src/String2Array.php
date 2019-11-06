<?php
namespace sqd;


/**
 * Parses a string like "maFonction(arg1, arg2)"
 * in {name: 'maFonction', args: ['arg1', 'arg2']}
 * 
 * @param string    $fun_str the function "signature" like "my_fun(arg1, arg2)
 *                  this does not support named arguments like my_fun(var1=arg1)
 * 
 * @return array    with this structure : ['name' => string, 'args' => array<string>]
 */
function function_signature_to_array($fun_str) {

    preg_match("/^([^\(]+)\((.+)\)\s*$/", $fun_str, $matches);
        
    return [
        'name' => (count($matches) > 0) ? trim($matches[1]): trim($fun_str),
        'args' => (count($matches) > 1) ? array_map(function($v) {return trim($v);}, explode(',', $matches[2])) : [],
    ];
}

?>