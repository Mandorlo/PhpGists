<?php
namespace ccn;

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


?>