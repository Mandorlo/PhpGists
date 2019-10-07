<?php

/**
 * Creates a test file for the file $file_path_to_test
 * by default it creates it in the tests folder, from the _Test.php.model template
 */
function create_test($file_path_to_test, $test_template_path = '') {
    if (substr($file_path_to_test, -4) != '.php') $file_path_to_test .= '.php';
    if (!file_exists($file_path_to_test)) {
        $file_path_to_test = realpath(__DIR__.'/../src/'.$file_path_to_test);
        if (!file_exists($file_path_to_test)) die($file_path_to_test . ' is not a file');
    }
    if (substr($file_path_to_test, -4) != '.php') die($file_path_to_test . ' is not a PHP file');

    $filename = basename($file_path_to_test, '.php');

    $test_filename = $filename.'Test.php';
    $test_filepath = __DIR__.'/../tests/'.$test_filename;
    
    if (!file_exists($test_filepath)) {
        if ($test_template_path == '') $test_template_path = __DIR__.'/../tests/_Test.php.model';
        if (!file_exists($test_template_path)) die('The tests template file '.$test_template_path.' does not exist');
        $test_file_contents = file_get_contents($test_template_path);
        
        $test_file_contents = str_replace('__MODULE__', $filename, $test_file_contents);
        
        file_put_contents($test_filepath, $test_file_contents);
        echo "Gloria a Dio, the file ".$test_filepath." has been successfully created !";
    } else {
        echo "TODO...";
    }

}

function get_defined_functions_in_file($file) {
    if (!file_exists($file)) return [];
    $source = file_get_contents($file);
    $tokens = token_get_all($source);

    $functions = array();
    $nextStringIsFunc = false;
    $inClass = false;
    $bracesCount = 0;

    foreach($tokens as $token) {
        switch($token[0]) {
            case T_CLASS:
                $inClass = true;
                break;
            case T_FUNCTION:
                if(!$inClass) $nextStringIsFunc = true;
                break;

            case T_STRING:
                if($nextStringIsFunc) {
                    $nextStringIsFunc = false;
                    $functions[] = $token[1];
                }
                break;

            // Anonymous functions
            case '(':
            case ';':
                $nextStringIsFunc = false;
                break;

            // Exclude Classes
            case '{':
                if($inClass) $bracesCount++;
                break;

            case '}':
                if($inClass) {
                    $bracesCount--;
                    if($bracesCount === 0) $inClass = false;
                }
                break;
        }
    }

    return $functions;
}
/* $r = get_defined_functions_in_file(__DIR__.'/../src/Array.php');
var_dump($r); */


var_dump($argv);
if (count($argv) < 2) die('no function specified');
if (!function_exists($argv[1])) die('function '.$argv[1].' is not a function');
call_user_func_array($argv[1], array_slice($argv, 2));
?>