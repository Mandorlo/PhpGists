<?php
namespace sqd;

require_once('Array.php');
require_once('String2Array.php');

/**
 * Powerfully transforms a json/array into another json/array with a different structure
 * 
 * @param array $model The model of the target json structure mapped to the source json structure
 * @param array $data The data (organized in source json structure) to be transformed in target json structure
 * @param array $context Some optional context data to be injected during the transform
 * 
 */
class ModelBuilder {

    private $model = [];
    private $data = [];
    private $context = [];
    
    // internal variables
    public $full_model = [];
    private $current_result = null;
    private $current_result_name = '';

    function __construct($model = [], $data = [], $context = []) {
        $this->model = $model;
        $this->full_model = $model;
        $this->data = $data;
        $this->context = $context;

        // we add some predefined modifiers to the context
        if (!isset($this->context['REGEX'])) {
            $this->context['REGEX'] = function($regex, $s, $default = '') use ($data) {
                if (strlen($s) > 0 && $s[0] == '/') $s = get_obj_path($data, $s);
                preg_match($regex, $s, $matches);
                if (count($matches) > 2) return array_splice($matches, 1);
                if (count($matches) == 2) return $matches[1];
                if (count($matches) == 1) return $matches[0];
                return $this->get_var($default);
            };
        }

        if (!isset($this->context['MAPPER'])) {
            $this->context['MAPPER'] = function($arr, $key, $default = '') {
                if (isset($arr[$key])) return $arr[$key];
                return $default;
            };
        }
    }

    public function build() {
        /**
         * The main function to be used 
         * to put the $this->data in the $this->model structure
         */
        $result = [];

        foreach ($this->model as $field => $rule) {

            // special case of conditional fields (starting with '?')
            if (strlen($field) > 1 && $field[0] == '?') {
                if (!isset($rule['__condition']) || !isset($rule['__value']) || !is_callable($rule['__condition'])) continue;
                if (!call_user_func_array($rule['__condition'], [$this->data, $this->context, $result])) continue;
                $field = substr($field, 1);
                $rule = $rule['__value'];
            }

            // special case of "__" commands (like __ADD_CONTEXT(var_name))
            if (preg_match('/^__ADD_CONTEXT/', $field)) {
                $field_data = $this->function_signature_to_array($field);
                $args = $field_data['args'];
                if ($field_data['name'] != '__ADD_CONTEXT' || empty($args) || empty($args[0])) continue;
                else if (is_callable($rule)) $this->context[$args[0]] = $rule($this->data, $this->context, $result);
                else if (gettype($rule) == 'string') $this->context[$args[0]] = $this->exec_string($rule);   
                else $this->context[$args[0]] = $rule;
                continue;
            }

            // normal fields
            if (is_array($rule)) {
                $this->model = $rule;
                $result[$field] = $this->build();
            } else if (gettype($rule) == 'string') {
                $result[$field] = $this->exec_string($rule);
            } else if (is_callable($rule)) {
                $result[$field] = $rule($this->data, $this->context, $result);
            } else {
                $result[$field] = $rule;
            }

        }
    
        $this->model = $this->full_model;
        return $result;
    }

    private function get_path($str) {
        /**
         * Retrieves the path like the function get_obj_path
         * but applying the path either to $data or $this->current_result
         * and taking care of default value when $str = "/the/path || []" for ex
         */

        $path = $str; $default = '';
        
        // get the default value if there is one
        if (strpos($str, '||') !== false) {
            $s_split = explode('||', $str);
            $path = trim($s_split[0]);
            $default = trim($s_split[1]);
            // we parse the default
            if ($default[0] == '$') {
                $var_name = substr($default, 1);
                if ($var_name == $this->current_result_name) $default = $this->current_result;
                else if (isset($this->context[$var_name])) $default = $this->context[$var_name];
            } else if ($default[0] == '[' || $default[0] == '{') {
                $default = json_decode($default);
            } else if ($default[0] == '"' && substr($default, -1) == '"') {
                $default = substr($default, 1, -1);
            }
        }
        return ($this->current_result == null) ? 
            get_obj_path($this->data, $path, "/", $default)
            : get_obj_path($this->current_result, $path, "/", $default);

    }

    private function get_var($arg) {
        /**
         * Retrieves and parses a string variable
         */
        if (strlen($arg) > 1 && $arg[0] == '/' && !preg_match("/^\/.+\/g?i?$/", $arg)) { // strlen($arg) > 1 && $arg[0] == '/' && substr($arg, -1) != '/'
            if ($this->current_result != null) $arg = $this->get_path($arg, $this->current_result);
            else $arg = $this->get_path($arg);
        } else if (strlen($arg) > 1 && $arg[0] == '$') {
            $var_name = substr($arg, 1);
            if ($var_name == $this->current_result_name) $arg = $this->current_result;
            else if (isset($this->context[$var_name])) $arg = $this->context[$var_name];
        } else if (strlen($arg) > 1 && $arg[0] == '[' || $arg[0] == '{') {
            $arg = json_decode($arg);
        } else if (strlen($arg) > 1 && $arg[0] == '"' && substr($arg, -1) == '"') {
            $arg = substr($arg, 1, -1);
        }
        return $arg;
    }

    private function apply_modifier($str) {
        /**
         * Applies a modifier like @REGEX(/^chose$/, /a/b/1, DEFAULT)
         */
        
        // we parse the function string like "@myfun(truc, bidule)"
        $fn_data = function_signature_to_array($str);
        if ($fn_data['name'][0] == '@') $fn_data['name'] = substr($fn_data['name'], 1);

        // we get the callable function
        if (!isset($this->context[$fn_data['name']])) return null;
        if (isset($this->context[$fn_data['name']]) && is_callable($this->context[$fn_data['name']])) $current_fn = $this->context[$fn_data['name']];
        if (!is_callable($current_fn)) return null;

        $args = $fn_data['args'];
        // we prepare the function arguments
        foreach ($args as &$arg) {
            $arg = $this->get_var($arg, $this->current_result);
        }
        $args[] = $this->data; // we add the original data
        $args[] = $this->current_result; // we add the current result

        // we call the modifier on the arguments
        return call_user_func_array($current_fn, $args);
    }

    private function exec_string($str) {
        /**
         * Executes a string like "@REGEX(/^([\d]+)\s+ans$/, /a/f, false)  |r>  @MAPPER($taille, $r)"
         */

        // we reset current_result
        $this->current_result = null;

        // split the string in the different piped parts
        $regex_split = "/\s+\|([^\>]+)\>\s+/";
        $arr_str = preg_split($regex_split, $str);
        preg_match_all($regex_split, $str, $matches);
        $current_result_names = $matches[1];

        $iter = 1;

        foreach ($arr_str as $s) {
            if ($iter > 1 && count($current_result_names) > $iter-2) $this->current_result_name = $current_result_names[$iter-2];
            
            // if it's a path, we get it
            if (strlen($s) > 0 && $s[0] == '/') {
                $this->current_result = $this->get_path($s, $this->current_result);

            // if it's a modifier, we apply it
            } else if (strlen($s) > 0 && $s[0] == '@') {
                $this->current_result = $this->apply_modifier($s, $this->current_result);
            
            } else {
                $this->current_result = $s;
            }

            $iter++;
        }

        return $this->current_result;
    }
}

?>