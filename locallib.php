<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Common functions of VPL
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib_consts.php');
require_once(dirname(__FILE__) . '/vpl.class.php');

/**
 * Set get vpl session var
 *
 * @param string $varname name of the session var without 'vpl_'
 * @param string $default default value
 * @param string $parname optional parameter name
 * @return $varname/param value
 * @codeCoverageIgnore
 */
function vpl_get_set_session_var($varname, $default, $parname = null) {
    global $SESSION;
    if ($parname == null) {
        $parname = $varname;
    }
    $res = $default;
    $fullname = 'vpl_' . $varname;
    if (isset($SESSION->$fullname)) {
        $res = $SESSION->$fullname;
    }
    $res = optional_param($parname, $res, PARAM_RAW);
    $SESSION->$fullname = $res;
    return $res;
}

/**
 * Create directory if not exist
 *
 * @param string $dir path to directory
 * @codeCoverageIgnore
 */
function vpl_create_dir($dir) {
    global $CFG;
    if (! @file_exists($dir)) { // Create dir?
        if (! @mkdir($dir, $CFG->directorypermissions, true)) {
            throw new file_exception('storedfileproblem', 'Error creating a directory to save files in VPL');
        }
    }
    if (! @is_dir($dir)) { // Is a file?
        throw new file_exception('storedfileproblem', "Error creating directory in VPL.");
    }
}


/**
 * Open/create a file and its dir
 *
 * @param string $filename string path to file
 * @return object file descriptor
 * @codeCoverageIgnore
 */
function vpl_fopen($filename) {
    if (! @file_exists($filename)) { // Exists file?
        $dir = dirname($filename);
        vpl_create_dir($dir);
    }
    if (@is_dir($filename)) { // Is a dir?
        throw new file_exception('storedfileproblem', "Error creating file in VPL.");
    }
    $fp = fopen($filename, 'w+b');
    if ($fp === false) {
        if (DIRECTORY_SEPARATOR == '\\') {
            $pathparts = pathinfo($filename);
            $name = $pathparts['filename'];
            if (preg_match('/(^aux$)|(^con$)|(^prn$)|(^nul$)|(^com\d$)|(^lpt\d$)/i', $name) == 1) {
                $patchedfilename = $pathparts['dirname'] . '\\_n_p_' . $pathparts['basename'];
                return vpl_fopen($patchedfilename);
            }
        }
    }
    if ($fp === false) {
        debugging("Error creating file in VPL '$filename'.", DEBUG_NORMAL);
        throw new file_exception('storedfileproblem', "Error creating file in VPL.");
    }
    return $fp;
}

/**
 * Open/create a file and its dir and write contents
 *
 * @param string $filename Path to the file to open
 * @param string $contents Contents to write into the file
 * @throws file_exception
 * @codeCoverageIgnore
 */
function vpl_fwrite($filename, $contents) {
    if (@is_file($filename)) {
        @unlink($filename);
    }
    $fd = vpl_fopen($filename);
    $res = ftruncate($fd, 0);
    $res = $res && (fwrite($fd, $contents) !== false);
    $res = fclose($fd) && $res;
    if ($res === false) {
        throw new file_exception('storedfileproblem', 'Error writing a file in VPL');
    }
}

/**
 * Recursively delete a directory
 *
 * @param string $dirname Name of the directory to delete
 * @return bool true if the directory was deleted, false otherwise
 * @codeCoverageIgnore
 */
function vpl_delete_dir($dirname) {
    $ret = false;
    if (@file_exists($dirname)) {
        $ret = true;
        if (@is_dir($dirname)) {
            $dd = opendir($dirname);
            if (! $dd) {
                return false;
            }
            $list = [];
            while ($name = readdir($dd)) {
                if ($name != '.' && $name != '..') {
                    $list[] = $name;
                }
            }
            closedir($dd);
            foreach ($list as $name) {
                $ret = vpl_delete_dir($dirname . '/' . $name) && $ret;
            }
            $ret = @rmdir($dirname) && $ret;
        } else {
            $ret = @unlink($dirname);
        }
    }
    return $ret;
}

/**
 * Outputs a zip file and removes it. Must be called before any other output
 *
 * @param string $zipfilename Name of the ZIP file with the data
 * @param string $name of file to be shown, without '.zip'
 * @codeCoverageIgnore
 */
function vpl_output_zip($zipfilename, $name) {
    if (! file_exists($zipfilename)) {
        debugging("Zip file not found " . $zipfilename, DEBUG_DEVELOPER);
        throw new moodle_exception('error:zipnotfound', 'mod_vpl');
    }
    // Send zipdata.
    $blocksize = 1000 * 1024;
    $size = filesize($zipfilename);
    $cname = rawurlencode($name . '.zip');
    $contentdisposition = 'Content-Disposition: attachment;';
    $contentdisposition .= ' filename="' . $name . '.zip";';
    $contentdisposition .= ' filename*=utf-8\'\'' . $cname;

    @header('Content-Length: ' . $size);
    @header('Content-Type: application/zip; charset=utf-8');
    @header($contentdisposition);
    @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
    @header('Content-Transfer-Encoding: binary');
    @header('Expires: 0');
    @header('Pragma: no-cache');
    @header('Accept-Ranges: none');
    // Get zip data.
    $offset = 0;
    while ($offset < $size) {
        echo file_get_contents($zipfilename, false, null, $offset, $blocksize);
        $offset += $blocksize;
    }
    // Remove zip file.
    unlink($zipfilename);
}

/**
 * Get locale from current lang for using in Linux.
 *
 * @return string
 * @codeCoverageIgnore
 */
function vpl_get_lang() {
    $lang = get_string('locale', 'langconfig');
    if (empty($lang) || $lang[0] == '[') {
        $lang = 'en_US.UTF-8';
    }
    return $lang;
}

/**
 * generate URL to page with params
 *
 * param $page string page from wwwroot
 * param string $parm1 name of the first parameter
 * param string $value1 value of the first parameter
 * param string $parm2 name of the second parameter
 * param string $value2 value of the second parameter
 * etc.
 * @codeCoverageIgnore
 */
function vpl_abs_href() {
    global $CFG;
    $parms = func_get_args();
    $l = count($parms);
    $href = $CFG->wwwroot . $parms[0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $href .= ($p > 1 ? '&amp;' : '?') . urlencode($parms[$p]) . '=' . urlencode($parms[$p + 1]);
    }
    return $href;
}

/**
 * generate URL to page with params
 *
 * param $page string page from wwwroot/mod/vpl/
 * param string $parm1 name of the first parameter
 * param string $value1 value of the first parameter
 * param string $parm2 name of the second parameter
 * param string $value2 value of the second parameter
 * etc.
 * @codeCoverageIgnore
 */
function vpl_mod_href() {
    global $CFG;
    $parms = func_get_args();
    $l = count($parms);
    $href = $CFG->wwwroot . '/mod/vpl/' . $parms[0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $href .= ($p > 1 ? '&amp;' : '?') . urlencode($parms[$p]) . '=' . urlencode($parms[$p + 1]);
    }
    return $href;
}

/**
 * Return 'gradeoun' or 'grade' for backward compatibility.
 * @todo This function is to be remove when Moodle 3.10 be not supported by VPL.
 *
 * @return string
 * @codeCoverageIgnore
 */
function vpl_get_gradenoun_str() {
    if (get_string_manager()->string_exists('gradenoun', 'core')) {
        return 'gradenoun';
    }
    return 'grade';
}

/**
 * Generate URL relative page with params
 *
 * param string $url URL to the page
 * param string $parm1 name of the first parameter
 * param string $value1 value of the first parameter
 * param string $parm2 name of the second parameter
 * param string $value2 value of the second parameter
 * etc.
 * @return string URL with parameters
 * @codeCoverageIgnore
 */
function vpl_rel_url() {
    $parms = func_get_args();
    $l = count($parms);
    $url = $parms[0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $url .= ($p > 1 ? '&amp;' : '?') . urlencode($parms[$p]) . '=' . urlencode($parms[$p + 1]);
    }
    return $url;
}

/**
 * Add a parm to a url
 *
 * @param string $url
 * @param string $parm name
 * @param string $value value of parm
 * @codeCoverageIgnore
 */
function vpl_url_add_param($url, $parm, $value) {
    if (strpos($url, '?')) {
        return $url . '&amp;' . urlencode($parm) . '=' . urlencode($value);
    } else {
        return $url . '?' . urlencode($parm) . '=' . urlencode($value);
    }
}

/**
 * Print a message and redirect
 *
 * @param string $link The URL to redirect to
 * @param string $message to be print
 * @param string $type of message (success, info, warning, error). Default = info
 * @param string $errorcode optional error code to show
 */
function vpl_redirect($link, $message, $type = 'info', $errorcode = '') {
    global $OUTPUT;
    if (! mod_vpl::header_is_out()) {
        echo $OUTPUT->header();
    }
    echo $OUTPUT->notification($message, $type);
    echo $OUTPUT->continue_button($link);
    echo $OUTPUT->footer();
    die();
}

/**
 * Inmediate redirect
 *
 * @param string $url URL to redirect to
 * @codeCoverageIgnore
 */
function vpl_inmediate_redirect($url) {
    global $OUTPUT;
    if (! mod_vpl::header_is_out()) {
        echo $OUTPUT->header();
    }
    static $idcount = 0;
    $idcount++;
    $text = '<div class="continuebutton"><a id="vpl_red' . $idcount . '" href="';
    $text .= $url . '">' . get_string('continue') . '</a></div>';
    $deco = urldecode($url);
    $deco = html_entity_decode($deco);
    echo vpl_include_js('window.location.replace("' . $deco . '");');
    echo $text;
    echo $OUTPUT->footer();
    die();
}

/**
 * Set JavaScript file from subdir jscript to be load
 *
 * @param string $file name of file to load
 * @param boolean $defer optional set if the load is inmediate or deffered
 * @codeCoverageIgnore
 */
function vpl_include_jsfile($file, $defer = true) {
    global $PAGE;
    $PAGE->requires->js(new moodle_url('/mod/vpl/jscript/' . $file), ! $defer);
}

/**
 * Set JavaScript code to be included
 *
 * @param string $jscript JavaScript code
 * @codeCoverageIgnore
 */
function vpl_include_js($jscript) {
    if ($jscript == '') {
        return '';
    }
    $ret = '<script type="text/javascript">';
    $ret .= "\n//<![CDATA[\n";
    $ret .= $jscript;
    $ret .= "\n//]]>\n</script>\n";
    return $ret;
}

/**
 * Popup message box to show text
 *
 * @param string $text to show. It use s() to sanitize text
 * @param boolean $print or not
 * @codeCoverageIgnore
 */
function vpl_js_alert($text, $print = true) {
    $aux = addslashes($text); // Sanitize text.
    $aux = str_replace("\n", "\\n", $aux); // Add \n to show multiline text.
    $aux = str_replace("\r", "", $aux); // Remove \r.
    $ret = vpl_include_js('alert("' . $aux . '");');
    if ($print) {
        echo $ret;
        @ob_flush();
        flush();
    } else {
        return $ret;
    }
}

/**
 * Returns an array with the format [time in seconds] => text.
 *
 * The first element is [0] => select.
 *
 * @param int $maximum The maximum time in seconds to generate.
 * @return array Key value => Text value
 * @codeCoverageIgnore
 */
function vpl_get_select_time($maximum = null) {
    $minute = 60;
    if ($maximum === null) { // Default value.
        $maximum = 120 * $minute;
    }
    $ret = [
            0 => get_string('select'),
    ];
    if ($maximum <= 0) {
        return $ret;
    }
    $value = 1;
    if ($maximum < $value) {
        $value = $maximum;
    }
    while ($value <= $maximum) {
        if ($value < $minute) {
            $ret[$value] = get_string('numseconds', '', $value);
        } else {
            $num = (int) ($value / $minute);
            $ret[$num * $minute] = get_string('numminutes', '', $num);
            $value = $num * $minute;
        }
        $value *= 2;
    }
    return $ret;
}

/**
 * Converts a size in byte to string in Kb, Mb, Gb and Tb.
 * Follows IEC "Prefixes for binary multiples".
 *
 * @param int $size Size in bytes
 * @return string
 * @codeCoverageIgnore
 */
function vpl_conv_size_to_string($size) {
    static $measure = [
            1024,
            1048576,
            1073741824,
            1099511627776,
            PHP_INT_MAX,
    ];
    static $measurename = [
            'KiB',
            'MiB',
            'GiB',
            'TiB',
    ];
    for ($i = 0; $i < count($measure) - 1; $i++) {
        if ($measure[$i] <= 0) { // Check for int overflow.
            $num = $size / $measure[$i - 1];
            return sprintf('%.2f %s', $num, $measurename[$i - 1]);
        }
        if ($size < $measure[$i + 1]) {
            $num = $size / $measure[$i];
            if ($size % $measure[$i] == 0) {
                return sprintf('%4d %s', $num, $measurename[$i]);
            } else {
                return sprintf('%.2f %s', $num, $measurename[$i]);
            }
        }
    }
}

/**
 * Return the array key after or equal to value
 *
 * @param array $array
 * @param int $value of key to search >=
 * @return int key found
 * @codeCoverageIgnore
 */
function vpl_get_array_key($array, int $value) {
    reset($array);
    $last = 0;
    while (($key = key($array)) !== null) {
        if ($key >= $value) {
            reset($array);
            return $key;
        }
        $last = $key;
        next($array);
    }
    reset($array);
    return $last;
}

/**
 * Returns un array with the format [size in bytes] => size in text.
 * The first element is [0] => select.
 *
 * @param int $minimum the initial value
 * @param int $maximum the limit of values generates
 * @return array Key value => Text value
 * @codeCoverageIgnore
 */
function vpl_get_select_sizes(int $minimum = 0, int $maximum = PHP_INT_MAX): array {
    $maximum = (int) $maximum;
    if ($maximum < 0) {
        $maximum = PHP_INT_MAX;
    }
    if ($maximum > 17.0e9) {
        $maximum = 16 * 1073741824;
    }
    $ret = [
            0 => get_string('select'),
    ];
    if ($minimum > 0) {
        $value = $minimum;
    } else {
        $value = 256 * 1024;
    }
    $pre = 0;
    $increment = $value / 4;
    while ($value <= $maximum && $value > 0) { // Avoid int overflow.
        $ret[$value] = vpl_conv_size_to_string($value);
        $pre = $value;
        $value += $increment;
        $value = (int) $value;
        if ($pre >= $value) { // Check for loop end.
            break;
        }
        if ($value >= 8 * $increment) {
            $increment = $value / 4;
        }
    }
    if ($pre < $maximum) { // Show limit value.
        $ret[$maximum] = vpl_conv_size_to_string($maximum);
    }
    return $ret;
}

/**
 * Detects end of line separator.
 *
 * @param string& $data Text to check.
 * @return string Newline separator "\r\n", "\n", "\r".
 * @codeCoverageIgnore
 */
function vpl_detect_newline(&$data) {
    // Detect text newline chars.
    if (strrpos($data, "\r\n") !== false) {
        return "\r\n"; // Windows.
    } else if (strrpos($data, "\n") !== false) {
        return "\n"; // UNIX.
    } else if (strrpos($data, "\r") !== false) {
        return "\r"; // Mac.
    } else {
        return "\n"; // Default Unix.
    }
}

/**
 * Print a message in the page
 *
 * @param string $text Text to show
 * @param string $type Type of message (success, info, warning, error). Default = success
 * @codeCoverageIgnore
 */
function vpl_notice(string $text, $type = 'success') {
    global $OUTPUT;
    echo $OUTPUT->notification($text, $type);
}

/**
 * Remove trailing right zeros from a float as string
 *
 * @param string $value float to remove right zeros
 * @return string
 * @codeCoverageIgnore
 */
function vpl_rtzeros($value) {
    if (strpos($value, '.') || strpos($value, ',')) {
        return rtrim(rtrim($value, '0'), '.,');
    }
    return $value;
}

/**
 * Generate an array with index an values $url.index
 *
 * @param string $url base
 * @param array $array of index
 * @return array with index as key and url as value
 * @codeCoverageIgnore
 */
function vpl_select_index($url, $array) {
    $ret = [];
    foreach ($array as $value) {
        $ret[$value] = $url . $value;
    }
    return $ret;
}

/**
 * Generate an array ready to be use in $OUTPUT->select_url
 *
 * @param string $url base
 * @param array $array of values
 * @return array with url as key and text as value
 * @codeCoverageIgnore
 */
function vpl_select_array($url, $array) {
    $ret = [];
    foreach ($array as $value) {
        $ret[$url . $value] = get_string($value, VPL);
    }
    return $ret;
}

/**
 * Get file extension from filename
 *
 * @param string $filename
 * @return string file extension
 * @codeCoverageIgnore
 */
function vpl_fileextension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
}


/**
 * Get if filename has image extension
 *
 * @param string $filename
 * @return boolean
 * @codeCoverageIgnore
 */
function vpl_is_image($filename) {
    return preg_match('/^(gif|jpg|jpeg|png|ico)$/i', vpl_fileextension($filename)) == 1;
}

/**
 * Get if filename has audio extension
 *
 * @param string $filename
 * @return boolean
 * @codeCoverageIgnore
 */
function vpl_is_audio($filename) {
    $audioext = 'wav|aiff|pcm|mp3|aac|ogg|wma|m4a|flac|alac|ape|wv|amr';
    return preg_match('/^(' . $audioext . ')$/i', vpl_fileextension($filename)) == 1;
}

/**
 * Get if filename has binary extension or binary data
 *
 * @param string $filename
 * @param string $data file contents
 * @return bool
 * @codeCoverageIgnore
 */
function vpl_is_binary($filename, &$data = false) {
    if (vpl_is_image($filename) || vpl_is_audio($filename)) {
        return true;
    }
    $fileext = 'zip|jar|pdf|tar|bin|7z|arj|deb|gzip|';
    $fileext .= 'rar|rpm|dat|db|dll|rtf|doc|docx|odt|exe|com';
    if (preg_match('/^(' . $fileext . ')$/i', vpl_fileextension($filename)) == 1) {
        return true;
    }
    if ($data === false) {
        return false;
    }
    return mb_detect_encoding($data, 'UTF-8', true) != 'UTF-8';
}

/**
 * Return data encoded to base64
 *
 * @param string $filename
 * @param string $data file contents
 * @return string
 * @codeCoverageIgnore
 */
function vpl_encode_binary($filename, &$data) {
    return base64_encode($data);
}

/**
 * Return data decoded from base64
 *
 * @param string $filename
 * @param string $data file contents
 * @return string
 * @codeCoverageIgnore
 */
function vpl_decode_binary($filename, $data) {
    return base64_decode($data);
}

/**
 * Return if path is valid
 *
 * @param string $path
 * @return boolean
 * @codeCoverageIgnore
 */
function vpl_is_valid_path_name($path) {
    if (strlen($path) > 256) {
        return false;
    }
    $dirs = explode('/', $path);
    for ($i = 0; $i < count($dirs); $i++) {
        if (! vpl_is_valid_file_name($dirs[$i])) {
            return false;
        }
    }
    return true;
}

/**
 * Return if file or directory name is valid
 *
 * @param string $name
 * @return boolean
 * @codeCoverageIgnore
 */
function vpl_is_valid_file_name($name) {
    $backtick = chr(96); // Avoid warnning in codecheck.
    $regexp = '/[\x00-\x1f]|[:-@]|[{-~]|\\\\|\[|\]|[\/\^';
    $regexp .= $backtick . '´]|^\-|^ | $|^\.$|^\.\.$/';
    if (strlen($name) < 1) {
        return false;
    }
    if (strlen($name) > 128) {
        return false;
    }
    return preg_match($regexp, $name) === 0;
}

/**
 * Truncate string to the limit passed
 *
 * @param string $string
 * @param int $limit
 * @codeCoverageIgnore
 */
function vpl_truncate_string(&$string, $limit) {
    if (strlen($string) <= $limit) {
        return;
    }
    $string = substr($string, 0, $limit - 3) . '...';
}


/**
 * Export a variable to bash.
 *
 * This function is used to assign a value to an environment variable in Linux bash.
 * It handles different types of values: integers, strings, and arrays.
 * Each type is formatted appropriately for bash export:
 *  - Integers are exported directly
 *  - Strings are enclosed in single quotes with proper escaping
 *  - Arrays are exported as bash arrays with each element in single quotes
 *
 * @param string $var name of the variable
 * @param mixed $value value of the variable (int, string or array)
 * @return string bash export statement for the variable
 * @codeCoverageIgnore
 */
function vpl_bash_export($var, $value) {
    if (is_int($value)) {
        $ret = "export $var=$value\n";
    } else if (is_array($value)) {
        $ret = "export $var=( ";
        foreach ($value as $data) {
            $ret .= "'" . str_replace("'", "'\''", $data) . "' ";
        }
        $ret .= ")\n";
    } else {
        $ret = "export $var='";
        $ret .= str_replace("'", "'\''", $value);
        $ret .= "'\n";
    }
    return $ret;
}

/**
 * For debug purpose
 *
 * Return content of vars ready to HTML
 *
 * @return string HTML ready content of var_dump
 * @codeCoverageIgnore
 */
function vpl_s() {
    $var = func_get_args();
    ob_start();
    call_user_func_array('var_dump', $var);
    $content = ob_get_contents();
    ob_end_clean();
    return htmlspecialchars($content, ENT_QUOTES);
}

/**
 * Truncate string fields of the VPL record instance
 *
 * @param object $instance object with the record
 * @codeCoverageIgnore
 */
function vpl_truncate_vpl($instance) {
    if (isset($instance->password)) {
        $instance->password = trim($instance->password);
    }
    if (property_exists($instance, 'jailservers') &&  $instance->jailservers == null) {
        $instance->jailservers = '';
    }
    foreach (['name', 'requirednet', 'password', 'variationtitle'] as $field) {
        if (isset($instance->$field)) {
            vpl_truncate_string($instance->$field, 255);
        }
    }
}

/**
 * Truncate string fields of the variation record instance
 *
 * @param object $instance object with the variation instance
 * @return void
 * @codeCoverageIgnore
 */
function vpl_truncate_variations($instance) {
    vpl_truncate_string($instance->identification, 40);
}

/**
 * Truncate string fields of the running_processes record instance
 *
 * @param object $instance object with the record
 * @codeCoverageIgnore
 */
function vpl_truncate_running_processes($instance) {
    vpl_truncate_string($instance->server, 255);
}

/**
 * Truncate string fields of the jailservers table
 *
 * @param object $instance object with the record
 * @codeCoverageIgnore
 */
function vpl_truncate_jailservers($instance) {
    vpl_truncate_string($instance->laststrerror, 255);
    vpl_truncate_string($instance->server, 255);
}

/**
 * Check if IP is within networks
 *
 * @param string $networks string with conma separate networks
 * @param string $ip string optional with the IP to check, if omited then remote IP
 * @return boolean true found
 * @codeCoverageIgnore
 */
function vpl_check_network($networks, $ip = false) {
    $networks = trim($networks);
    if ($networks == '') {
        return true;
    }
    if ($ip === false) {
        $ip = getremoteaddr();
    }
    return address_in_subnet($ip, $networks);
}

/**
 * Get awesome icon for action
 *
 * @param string $str name of the icon
 * @param string $classes additional classes to add to the icon
 * @return string
 * @codeCoverageIgnore
 */
function vpl_get_awesome_icon($str, $classes = '') {
    $icon = 'mod_vpl:' . $str;
    $imap = mod_vpl_get_fontawesome_icon_map();
    if (isset($imap[$icon])) {
        $ficon = $imap[$icon];
        return '<i class="fa ' . $ficon . $classes . '"></i> ';
    }
    return '';
}


/**
 * Create a new tabobject for navigation
 *
 * @param String $id
 * @param string|moodle_url $href
 * @param string $str to be i18n
 * @param string $comp component
 * @return tabobject
 * @codeCoverageIgnore
 */
function vpl_create_tabobject($id, $href, $str, $comp = 'mod_vpl') {
    $stri18n = get_string($str, $comp);
    $strdescription = vpl_get_awesome_icon($str) . $stri18n;
    return new tabobject($id, $href, $strdescription, $stri18n);
}

/**
 * Get version string.
 *
 * @return string
 * @codeCoverageIgnore
 */
function vpl_get_version(): string {
    static $version = '';
    if ($version === '' && false) { // Removed version information.
        $plugin = new stdClass();
        require_once(dirname(__FILE__) . '/version.php');
        $version = $plugin->release;
    }
    return $version;
}

/**
 * Polyfill for getting user picture fields.
 *
 * @return string List of fields separated by "," u.field
 * @codeCoverageIgnore
 */
function vpl_get_picture_fields(): string {
    if (method_exists('\core_user\fields', 'get_picture_fields')) {
        return 'u.' . implode(',u.', \core_user\fields::get_picture_fields());
    } else {
        return user_picture::fields('u');
    }
}

/**
 * Return array of override objects for a vpl activity.
 * Asigned override as agregate in fields userids and groupids.
 *
 * @param array $overrides array of override objects
 * @param array $asignedoverrides array of asigned override objects
 * @return array of override objects with userids and groupids fields
 * @codeCoverageIgnore
 */
function vpl_agregate_overrides($overrides, $asignedoverrides): array {
    $userids = [];
    $groupids = [];
    foreach ($overrides as $override) {
        $userids[$override->id] = [];
        $groupids[$override->id] = [];
    }

    foreach ($asignedoverrides as $asigned) {
        $oid = $asigned->override;
        if (isset($userids[$oid])) { // TODO check consistence for false?
            if (!empty($asigned->userid)) {
                $userids[$oid][] = $asigned->userid;
            }
            if (!empty($asigned->groupid)) {
                $groupids[$oid][] = $asigned->groupid;
            }
        }
    }
    foreach ($overrides as $override) {
        $override->userids = implode(',', $userids[$override->id]);
        $override->groupids = implode(',', $groupids[$override->id]);
    }
    return $overrides;
}

/**
 * Return array of override objects for a vpl activity.
 * Asigned override as agregate userids and groupids.
 *
 * @param int $vplid VPL ID to get overrides for.
 * @return array of override objects
 * @codeCoverageIgnore
 */
function vpl_get_overrides($vplid): array {
    global $DB;
    $sql = 'SELECT * FROM {vpl_overrides}
            WHERE vpl = :vplid
            ORDER BY id ASC';
    $overrides = $DB->get_records_sql($sql, ['vplid' => $vplid]);

    $sql = 'SELECT * FROM {vpl_assigned_overrides}
            WHERE vpl = :vplid';
    $asignedoverrides = $DB->get_records_sql($sql, ['vplid' => $vplid]);

    return vpl_agregate_overrides($overrides, $asignedoverrides);
}

/**
 * Return array of override objects for a course.
 * Asigned override as agregate userids and groupids.
 *
 * @param int $courseid Course ID to get overrides for.
 * @return array of override objects
 * @codeCoverageIgnore
 */
function vpl_get_overrides_incourse($courseid): array {
    global $DB;
    $sql = 'SELECT * FROM {vpl_overrides}
            WHERE vpl IN (SELECT id FROM {vpl} WHERE course = :courseid)
            ORDER BY id ASC';
    $overrides = $DB->get_records_sql($sql, ['courseid' => $courseid]);

    $sql = 'SELECT * FROM {vpl_assigned_overrides}
            WHERE vpl IN (SELECT id FROM {vpl} WHERE course = :courseid)';
    $asignedoverrides = $DB->get_records_sql($sql, ['courseid' => $courseid]);

    return vpl_agregate_overrides($overrides, $asignedoverrides);
}

/**
 * Calls a function with lock.
 *
 * @param string $locktype Name of the lock type (unique)
 * @param string $resource Name of the resourse (unique)
 * @param string $function Name of the function to call
 * @param array $parms Parameters to pass to the function
 * @return mixed Value returned by the function or throw exception
 */
function vpl_call_with_lock(string $locktype, string $resource, string $function, array &$parms) {
    $lockfactory = \core\lock\lock_config::get_lock_factory($locktype);
    if ($lock = $lockfactory->get_lock($resource, VPL_LOCK_TIMEOUT)) {
        try {
            $result = $function(...$parms);
            $lock->release();
            return $result;
        } catch (\Throwable $e) {
            $lock->release();
            throw $e;
        }
    } else {
        throw new moodle_exception('locktimeout');
    }
    return false;
}

/**
 * Calls a function with DB transactions.
 *
 * @param string $function Name of the function to call
 * @param array $parms Parameters to pass to the function
 * @return mixed Value returned by the function or throw exception
 */
function vpl_call_with_transaction(string $function, array $parms) {
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    try {
        $result = $function(...$parms);
        $transaction->allow_commit();
        return $result;
    } catch (\Throwable $e) {
        $transaction->rollback($e);
    }
}

/**
 * Return full path to the directory of scripts.
 *
 * @return string
 * @codeCoverageIgnore
 */
function vpl_get_scripts_dir() {
    global $CFG;
    return $CFG->dirroot . '/mod/vpl/jail/default_scripts';
}

/**
 * Generate HTML fragment representing an "info" icon.
 *
 * @return string HTML fragment
 * @codeCoverageIgnore
 */
function vpl_info_icon() {
    global $OUTPUT;
    return $OUTPUT->pix_icon('i/info', get_string('info'), 'moodle', [ 'class' => 'text-info' ]);
}

/**
 * Generate HTML fragment of a button to copy given text to clipboard.
 *
 * @param string $text Text to copy to clipboard.
 * @return string HTML fragment
 * @codeCoverageIgnore
 */
function vpl_get_copytoclipboard_control($text) {
    $text = addslashes(str_replace("\r", '', str_replace("\n", '\n', $text)));
    $strsuccess = addslashes(nl2br(get_string('copytoclipboardsuccess', VPL)));
    $strfailure = addslashes(nl2br(get_string('copytoclipboarderror', VPL)));
    $js = "
        var parentDiv = this.parentNode;
        var notify = function(message) {
            var x = document.createElement('span');
            x.textContent = message;
            x.classList = 'badge rounded mx-1 align-text-bottom';
            parentDiv.append(x);
            setTimeout(() => x.remove(), 1300);
        };
        navigator.clipboard.writeText('$text')
        .then(
            () => notify('$strsuccess'),
            () => notify('$strfailure')
        );";
    return html_writer::span(
        '<i class="fa fa-clone"></i>',
        'clickable btn-link text-decoration-none mx-1',
        [ 'title' => get_string('copytoclipboard', VPL), 'onclick' => $js ]
    );
}

/**
 * Print some text with a "copy to clipboard" button.
 * @param string $title A title to put before, will be non-selectable for easier select-copy of the text.
 * @param string $displayedinfo What will be displayed.
 * @param string|null $copyinfo Can be different from actual displayed info if provided.
 * @codeCoverageIgnore
 */
function vpl_print_copyable_info($title, $displayedinfo, $copyinfo = null) {
    if ($copyinfo === null) {
        $copyinfo = $displayedinfo;
    }
    echo html_writer::div('<span style="user-select:none;">' . $title . ' </span>' .
            '<b>' . $displayedinfo . '</b>' . vpl_get_copytoclipboard_control($copyinfo));
}
