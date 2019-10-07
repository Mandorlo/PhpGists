<?php
namespace ccn;

/**
 * Like array_filter but applies to files/dirs in $dir
 * 
 * @param string $dir the directory pathy
 * @param function $fun the filter of the form function(path) => bool
 * 
 * @return array list of dicts of the form {path:string; name:string; value:any} that represent the filtered files/dirs
 * 
 */
function dir_filter_fun($dir, $fun, $recursive = true){

    if (strpos($dir, '/') !== false) $dir = str_replace("\\", "/", $dir);
    $res = dir_map_fun($dir, $fun, $recursive);
    return array_filter($res, function($el) {
        return $el['value'];
    });
}

/**
 * Applies function $fun to all files and dirs in $dir (recusively or not)
 * Returns the list of {path => [value returned by $fun(path)]}
 * 
 * @param string $dir       the directory path
 * @param callable $fun     fonction($full_path, $file_name, $meta_info) to apply to all files and dirs
 *                          $meta_info is an array with info on the file returned by get_file_meta_info()
 * 
 * @return array            list of dicts of the form {path:string; name:string; value:any} 
 *                          value key contains the result of fn(path)
 * 
 */
function dir_map_fun($dir, $fun, $recursive = true){

    $results = array();
    $files = scandir($dir);
    if ($files === false) return false;

    foreach ($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);

        if(!is_dir($path)){
            $info = file_meta_info($path);
            $results[] = array(
                'path' => $path,
                'name' => $value,
                'value' => $fun($path, $value, $info)
            );

        } else if($value != "." && $value != "..") {
            $info = $this->get_file_meta_info($path);
            $results[] = array(
                'path' => $path,
                'name' => $value,
                'value' => $fun($path, $value, $info),
            );
            if ($recursive) $results = array_merge($results, $this->dir_map_fun($path, $fun, true));
        }
    }

    return $results;
}

/**
 * Returns some metadata on a file
 * 
 * @param string $path the path to a file
 * 
 * @return array the file meta info or ['error' => True] if file does not exist
 */
function file_meta_info($path) {
    if (!file_exists($path)) return ['error' => True];
    return array(
        'is_dir' => is_dir($path),
        'last_modification_date' => filemtime($path), // use date('Y-m', $meta_info['last_modification_date']); to format the way you want
        'type' => filetype($path),
        'size' => filesize($path), // in bytes/octets
        'owner' => fileowner($path),
        'perms' => fileperms($path), // returns an int - http://php.net/manual/fr/function.fileperms.php
    );
}

/**
 * Loads a json as associative array
 * from either a file or a http url
 * 
 * @param string    $path_or_url a path to a json file or a url that returns a json resource
 * @param array     $options cache options. It's an associative array with the following keys : 
 *                  * cache_dir (default = '' no cache) the cache directory, basename($path_or_url) is used as filename, 
 *                  * cache_path (default = '' no cache), the cache file path, overwrites cache_dir if set
 *                  * cache_lifetime (default = '30 day') any valid string for the strtotime function is accepted
 * 
 * @return array    the json resource as an associative array
 * @return false    if resource could not be found or parsed
 */
function load_json($path_or_url, $options = []) {

    $options_default = [
        "cache_dir" => "", // the cache directory (uses the $path_or_url basename as filename)
        "cache_path" => "", // the cache file path, overwrites cache_dir if set
        "cache_lifetime" => "30 day", // any valid time for the strtotime function
    ];
    $options = array_merge($options_default, $options);

    $cache_path = (!empty($options['cache_dir'])) ? $options['cache_dir'] . "/" . basename($path_or_url) : '';
    if ($cache_path == '' && is_file($options['cache_path'])) $cache_path = $options['cache_path'];

    if (is_file($cache_path)) {
        $oldest_authorized_date = strtotime('-' + $options['cache_lifetime'], strtotime('now'));
        $last_modif_date = filemtime($cache_path);

        // if date is recent enough, we send cache
        if ($oldest_authorized_date < $last_modif_date) {
            $s = file_get_contents($cache_path);
            return json_decode($s, true);
        }
    }

    // we get data from http url
    $data_str = file_get_contents($path_or_url);
    $data = json_decode($data_str, true);

    if (is_file($cache_path)) {
        // if data was retrieved from http, we update cache
        if (is_array($data)) {
            //$str = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents($cache_path, $data_str);
        // otherwise we send the cache
        } else {
            $s = file_get_contents($cache_path);
            return json_decode($s, true);
        }
    }
    
    return $data;
}

/**
 * Transforms a full path into a relative one, relative to $mask_path
 * 
 * @param string $mask_path
 * @param string $full_path
 * 
 * @return string the path relative to $mask_path, extracted from $full_path
 */
function path_full_to_relative($mask_path, $full_path) {

    $dir_sep = "/";
    $other_dir_sep = "\\";
    if (strpos($full_path, "\\") !== false) {
        $dir_sep = "\\";
        $other_dir_sep = "/";
    }
    
    $mask_path = str_replace($other_dir_sep, $dir_sep, $mask_path);
    if (substr($mask_path, -1) != $dir_sep) $mask_path .= $dir_sep;
    $full_path = str_replace($other_dir_sep, $dir_sep, $full_path);

    $ind = strpos($full_path, $mask_path);
    if ($ind === 0) return substr($full_path, strlen($mask_path));
    return $full_path;
}

?>