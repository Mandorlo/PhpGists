<?php
namespace ccn;


/**
 * This is a simple logger
 * 
 * It logs files in $this->params['dir']
 * The root log dir has several subfolders, corresponding to different channels, the main one is called "APP"
 * Logs are stored in files named log-2019-04.txt, so one for each month
 * Log files are deleted by default after 6 months, you can change this with $this->set_max_age([max number of months])
 */
class Logger {

    private $params = [
        'dir' => '', // the directory where we should store log files
        'owner_group' => 'www-data', // the owner group of log dirs and files
        'max_file_size' => 'todo', // starts a new file if file gets bigger than this
        'max_file_age' => '6', // in months. deleted all files older than max_file_age days
    ];

    function __construct($dir = '', $opt = []) {
        if (!empty($dir)) $this->params['dir'] = $dir;
        else $this->params['dir'] = __dir__.'/log';
        if (!empty($opt['max_file_age'])) $this->params['max_file_age'] = $opt['max_file_age'];
        if (!empty($opt['max_file_size'])) $this->params['max_file_size'] = $opt['max_file_size'];
    }

    public function get_dir() {
        /**
         * Returns the log directory
         */
        return $this->params['dir'];
    }
    
    public function get_params() {
        /**
         * Returns the log parameters
         */
    
        return $this->params;
    }

    public function set_max_age($nb_months) {
        /**
         * Sets the maximum age of a log file (file will be deleted after this age)
         */
        if (gettype($nb_months) != 'integer') return false;
        $this->params['max_file_age'] = $nb_months;
    }
    
    public function write($level, $title, $data, $channel = "") {
        /**
         * Writes a log in the log file
         * 
         * @param string level      Le niveau de log (INFO, WARNING, ERROR). N.B: le niveau INFO est loggé dans un sous-dossier INFO
         * @param string title      un titre pour cette entrée (ça peut être par ex un identifiant de l'erreur)
         * @param string data       soit une string soit un élément qui sera écrit comme json_encode(data)
         * @param string channel    cela va créer un sous-dossier avec le nom du channel dans le dossier de logs
         * 
         */
    
        // on clean les logs
        $this->clean_old_logs();
    
        // la date
        $curr_log_date = date('Y-m-d H:i:s');
    
        // le niveau et le titre
        $level = strtoupper($level);
        $title = strtoupper($title);
    
        // on génère le nom du fichier de log où il faudra écrire
        if (!$channel) $channel = 'APP';
        if ($channel && $channel[0] != '/') $channel = '/'.$channel;
        $log_dir = $this->params['dir'].$channel;
        $log_path = $log_dir.'/log-'.date('Y-m').'.txt';
    
        // le message
        $body = (gettype($data) == 'string') ? $data : json_encode($data);
    
        // on écrit le message
        $msg = $curr_log_date.' ::'.$level.'::'.$title.':: '.$body."\n";
    
        // créer les dossiers inexistants éventuellement
        if (!is_dir($log_dir)) {
            $b = mkdir($log_dir);
            if (!$b) return false;
            if (!empty($this->params['owner_group'])) chgrp($log_dir, $this->params['owner_group']);
            chmod($log_dir, 0775); // the 0 is needed !
        }

        // write message on file
        if (file_exists($log_path) && is_writable($log_path) !== true) {
            // get current username and group
            $userInfo = posix_getpwuid(posix_getuid());
            $user = $userInfo['name'];
            $groupInfo = posix_getgrgid(posix_getgid());
            $group = $groupInfo['name'];
            echo "log file not writable (user=$user group=$group)\n";
            return false;
        }
        $b = file_put_contents($log_path, $msg, FILE_APPEND | LOCK_EX);//file_force_contents($log_path, $msg);
        if (!empty($this->params['owner_group'])) chgrp($log_path, $this->params['owner_group']);
        chmod($log_path, 0770); // the first 0 is needed !
        return $b;
    }
    
    public function error($title = "", $data = "", $channel = "", $return_value = false) {
        // $return_value permet de renvoyer $return_value :)
        $this->write('ERROR', $title, $data, $channel);
        return $return_value;
    }
    
    public function warning($title = "", $data = "", $channel = "", $return_value = false) {
        $this->write('WARNING', $title, $data, $channel);
        return $return_value;
    }
    
    public function info($title = "", $data = "", $channel = "", $return_value = false) {
        $this->write('INFO', $title, $data, $channel);
        return $return_value;
    }
    
    // ================================================================
    //                  LOG CLEANING
    // ================================================================
    
    private function clean_old_logs() {
        /**
         * Deletes all logs that are too old
         */
    
        $log_dir = $this->get_dir();
        $max_file_age = $this->params['max_file_age']; // in months
        $interval = date_interval_create_from_date_string(-$max_file_age.' months');
        $oldest_date = date_add(date_create('now'), $interval);
        $oldest_date = date_format($oldest_date, 'Y-m');
        
        $delete_old_fun = function($full_path, $file_name, $meta_info) use ($oldest_date) {
            if ($meta_info['is_dir']) return false;
            $curr_date = date('Y-m', $meta_info['last_modification_date']);
            if ($curr_date <  $oldest_date) {
                unlink($full_path);
                return true;
            }
            return $curr_date;
        };
        return $this->dir_map_fun($log_dir, $delete_old_fun, true);
    }
    
    
    // ================================================================
    //                  HELPER FUNCTIONS
    // ================================================================
    
    private function dir_map_fun($dir, $fun, $recursive = true){
        /**
         * Applies function $fun to all files and dirs in $dir (recusively or not)
         * Returns the list of {path => [value returned by $fun(path)]}
         * 
         * @param string $dir       the directory path
         * @param callable $fun     fonction($full_path, $file_name, $meta_info) to apply to all files and dirs
         *                          $meta_info is an array with info on the file returned by get_file_meta_info()
         * 
         */
    
        $results = array();
        $files = scandir($dir);
        if ($files === false) return false;
    
        foreach ($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
    
            if(!is_dir($path)){
                $info = $this->get_file_meta_info($path);
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
    
    private function get_file_meta_info($path) {
        return array(
            'is_dir' => is_dir($path),
            'last_modification_date' => filemtime($path), // use date('Y-m', $meta_info['last_modification_date']); to format the way you want
            'type' => filetype($path),
            'size' => filesize($path), // in bytes/octets
            'owner' => fileowner($path),
            'perms' => fileperms($path), // returns an int - http://php.net/manual/fr/function.fileperms.php
        );
    }
    
    // crée tous les dossiers nécessaires pour que le chemin vers le dossier $dir existe
    private function file_force_contents($dir, $contents){
        /**
         * TODO NE MARCHE PAS !!!!
         */
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part)
            $a = $dir."/$part";
            if (!is_dir($dir .= "/$part")) mkdir($dir);
        return file_put_contents("$dir/$file", $contents, FILE_APPEND | LOCK_EX);
    }

}



?>