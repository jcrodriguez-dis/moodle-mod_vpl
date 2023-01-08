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

define( 'VPL', 'vpl' );
define( 'VPL_SUBMISSIONS', 'vpl_submissions' );
define( 'VPL_JAILSERVERS', 'vpl_jailservers' );
define( 'VPL_RUNNING_PROCESSES', 'vpl_running_processes' );
define( 'VPL_VARIATIONS', 'vpl_variations' );
define( 'VPL_ASSIGNED_VARIATIONS', 'vpl_assigned_variations' );
define( 'VPL_OVERRIDES', 'vpl_overrides' );
define( 'VPL_ASSIGNED_OVERRIDES', 'vpl_assigned_overrides' );
define( 'VPL_GRADE_CAPABILITY', 'mod/vpl:grade' );
define( 'VPL_VIEW_CAPABILITY', 'mod/vpl:view' );
define( 'VPL_SUBMIT_CAPABILITY', 'mod/vpl:submit' );
define( 'VPL_SIMILARITY_CAPABILITY', 'mod/vpl:similarity' );
define( 'VPL_ADDINSTANCE_CAPABILITY', 'mod/vpl:addinstance' );
define( 'VPL_SETJAILS_CAPABILITY', 'mod/vpl:setjails' );
define( 'VPL_MANAGE_CAPABILITY', 'mod/vpl:manage' );
define( 'VPL_EVENT_TYPE_DUE', 'duedate');
define( 'VPL_LOCK_TIMEOUT', 10);

require_once(dirname(__FILE__).'/vpl.class.php');

/**
 * @codeCoverageIgnore
 *
 * Set get vpl session var
 *
 * @param string $varname
 *            name of the session var without 'vpl_'
 * @param string $default
 *            default value
 * @param string $parname
 *            optional parameter name
 * @return $varname/param value
 */
function vpl_get_set_session_var($varname, $default, $parname = null) {
    global $SESSION;
    if ($parname == null) {
        $parname = $varname;
    }
    $res = $default;
    $fullname = 'vpl_' . $varname;
    if (isset( $SESSION->$fullname )) {
        $res = $SESSION->$fullname;
    }
    $res = optional_param( $parname, $res, PARAM_RAW );
    $SESSION->$fullname = $res;
    return $res;
}

/**
 * @codeCoverageIgnore
 *
 * Create directory if not exist
 *
 * @param $dir string path to directory
 */
function vpl_create_dir($dir) {
    global $CFG;
    if (! file_exists( $dir )) { // Create dir?
        if (! mkdir( $dir, $CFG->directorypermissions, true ) ) {
            throw new file_exception('storedfileproblem', 'Error creating a directory to save files in VPL');
        }
    }
}


/**
 * @codeCoverageIgnore
 *
 * Open/create a file and its dir
 *
 * @param $filename string path to file
 * @return Object file descriptor
 */
function vpl_fopen($filename) {
    if (! file_exists( $filename )) { // Exists file?
        $dir = dirname( $filename );
        vpl_create_dir($dir);
    }
    $fp = fopen( $filename, 'w+b' );
    if ($fp === false) {
        if (DIRECTORY_SEPARATOR == '\\' ) {
            $pathparts = pathinfo($filename);
            $name = $pathparts['filename'];
            if (preg_match( '/(^aux$)|(^con$)|(^prn$)|(^nul$)|(^com\d$)|(^lpt\d$)/i' , $name) == 1) {
                $patchedfilename = $pathparts['dirname'] . '\\_n_p_' . $pathparts['basename'];
                return vpl_fopen($patchedfilename);
            }
        }
    }
    if ($fp === false) {
        debugging( "Error creating file in VPL '$filename'.", DEBUG_NORMAL );
        throw new file_exception('storedfileproblem', "Error creating file in VPL.");
    }
    return $fp;
}

/**
 * @codeCoverageIgnore
 *
 * Open/create a file and its dir and write contents
 *
 * @param string $filename. Path to the file to open
 * @param string $contents. Contents to write into the file
 * @exception file_exception
 * @return void
 */
function vpl_fwrite($filename, $contents) {
    if ( is_file($filename) ) {
        unlink($filename);
    }
    $fd = vpl_fopen( $filename );
    $res = ftruncate ( $fd, 0);
    $res = $res && (fwrite( $fd, $contents ) !== false);
    $res = fclose( $fd ) && $res;
    if ($res === false) {
        throw new file_exception('storedfileproblem', 'Error writing a file in VPL');
    }
}

/**
 * @codeCoverageIgnore
 *
 * Recursively delete a directory
 *
 * @return bool All delete
 */
function vpl_delete_dir($dirname) {
    $ret = false;
    if (file_exists( $dirname )) {
        $ret = true;
        if (is_dir( $dirname )) {
            $dd = opendir( $dirname );
            if (! $dd) {
                return false;
            }
            $list = array ();
            while ( $name = readdir( $dd ) ) {
                if ($name != '.' && $name != '..') {
                    $list[] = $name;
                }
            }
            closedir( $dd );
            foreach ($list as $name) {
                $ret = vpl_delete_dir( $dirname . '/' . $name ) && $ret;
            }
            $ret = rmdir( $dirname ) && $ret;
        } else {
            $ret = unlink( $dirname );
        }
    }
    return $ret;
}

/**
 * @codeCoverageIgnore
 *
 * Outputs a zip file and removes it. Must be called before any other output
 *
 * @param string $zipfilename. Name of the ZIP file with the data
 * @param string $name of file to be shown, without '.zip'
 *
 */
function vpl_output_zip($zipfilename, $name) {
    if (! file_exists($zipfilename)) {
        debugging("Zip file not found " . $zipfilename, DEBUG_DEVELOPER);
        throw new moodle_exception('error:zipnotfound', 'mod_vpl');
    }
    // Send zipdata.
    $blocksize = 1000 * 1024;
    $size = filesize( $zipfilename );
    $cname = rawurlencode( $name . '.zip' );
    $contentdisposition = 'Content-Disposition: attachment;';
    $contentdisposition .= ' filename="' . $name . '.zip";';
    $contentdisposition .= ' filename*=utf-8\'\'' . $cname;

    @header( 'Content-Length: ' . $size );
    @header( 'Content-Type: application/zip; charset=utf-8' );
    @header( $contentdisposition );
    @header( 'Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0' );
    @header( 'Content-Transfer-Encoding: binary' );
    @header( 'Expires: 0' );
    @header( 'Pragma: no-cache' );
    @header( 'Accept-Ranges: none' );
    // Get zip data.
    $offset = 0;
    while ($offset < $size) {
        echo file_get_contents( $zipfilename, false,  null, $offset, $blocksize);
        $offset += $blocksize;
    }
    // Remove zip file.
    unlink( $zipfilename );
}

/**
 * @codeCoverageIgnore
 *
 * Get lang code @parm $bashadapt true adapt lang to bash LANG (default false)
 *
 * @return string
 */
function vpl_get_lang($bashadapt = false) {
    global $SESSION, $USER, $CFG;
    $commonlangs = array (
            'aa' => 'DJ',
            'af' => 'ZA',
            'am' => 'ET',
            'an' => 'ES',
            'az' => 'AZ',
            'ber' => 'DZ',
            'bg' => 'BG',
            'ca' => 'ES',
            'cs' => 'CZ',
            'da' => 'DK',
            'de' => 'DE',
            'dz' => 'BT',
            'en' => 'US',
            'es' => 'ES',
            'et' => 'EE',
            'fa' => 'IR',
            'fi' => 'FI',
            'fr' => 'FR',
            'he' => 'IL',
            'hu' => 'HU',
            'ig' => 'NG',
            'it' => 'IT',
            'is' => 'IS',
            'ja' => 'JP',
            'km' => 'KH',
            'ko' => 'KR',
            'lo' => 'LA',
            'lv' => 'LV',
            'pt' => 'PT',
            'ro' => 'RO',
            'ru' => 'RU',
            'se' => 'NO',
            'sk' => 'sk',
            'so' => 'SO',
            'sv' => 'SE',
            'or' => 'IN',
            'th' => 'th',
            'ti' => 'ET',
            'tk' => 'TM',
            'tr' => 'TR',
            'uk' => 'UA',
            'yo' => 'NG'
    );
    if (isset( $SESSION->lang )) {
        $lang = $SESSION->lang;
    } else if (isset( $USER->lang )) {
        $lang = $USER->lang;
    } else if (isset( $CFG->lang )) {
        $lang = $CFG->lang;
    } else {
        $lang = 'en';
    }
    if ($bashadapt) {
        $parts = explode( '_', $lang );
        if (count( $parts ) == 2) {
            $lang = $parts[0];
        }
        if (isset( $commonlangs[$lang] )) {
            $lang = $lang . '_' . $commonlangs[$lang];
        }
        $lang .= '.UTF-8';
    }
    return $lang;
}

/**
 * @codeCoverageIgnore
 *
 * generate URL to page with params
 *
 * @param $page string
 *            page from wwwroot
 * @param $var1 string
 *            var1 name optional
 * @param $value1 string
 *            value of var1 optional
 * @param $var2 string
 *            var2 name optional
 * @param $value2 string
 *            value of var2 optional
 * @param
 *            ...
 */
function vpl_abs_href() {
    global $CFG;
    $parms = func_get_args();
    $l = count( $parms );
    $href = $CFG->wwwroot . $parms[0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $href .= ($p > 1 ? '&amp;' : '?') . urlencode( $parms[$p] ) . '=' . urlencode( $parms[$p + 1] );
    }
    return $href;
}

/**
 * @codeCoverageIgnore
 *
 * generate URL to page with params
 *
 * @param $page string
 *            page from wwwroot/mod/vpl/
 * @param $var1 string
 *            var1 name optional
 * @param $value1 string
 *            value of var1 optional
 * @param $var2 string
 *            var2 name optional
 * @param $value2 string
 *            value of var2 optional
 * @param
 *            ...
 */
function vpl_mod_href() {
    global $CFG;
    $parms = func_get_args();
    $l = count( $parms );
    $href = $CFG->wwwroot . '/mod/vpl/' . $parms[0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $href .= ($p > 1 ? '&amp;' : '?') . urlencode( $parms[$p] ) . '=' . urlencode( $parms[$p + 1] );
    }
    return $href;
}

/**
 * @codeCoverageIgnore
 *
 * generate URL relative page with params
 *
 * @param $page string
 *            page relative
 * @param $var1 string
 *            var1 name optional
 * @param $value1 string
 *            value of var1 optional
 * @param $var2 string
 *            var2 name optional
 * @param $value2 string
 *            value of var2 optional
 * @param
 *            ...
 */
function vpl_rel_url() {
    $parms = func_get_args();
    $l = count( $parms );
    $url = $parms[0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $url .= ($p > 1 ? '&amp;' : '?') . urlencode( $parms[$p] ) . '=' . urlencode( $parms[$p + 1] );
    }
    return $url;
}
/**
 * @codeCoverageIgnore
 *
 * Add a parm to a url
 *
 * @param $url string
 * @param $parm string
 *            name
 * @param $value string
 *            value of parm
 */
function vpl_url_add_param($url, $parm, $value) {
    if (strpos( $url, '?' )) {
        return $url . '&amp;' . urlencode( $parm ) . '=' . urlencode( $value );
    } else {
        return $url . '?' . urlencode( $parm ) . '=' . urlencode( $value );
    }
}

/**
 * @codeCoverageIgnore
 *
 * Print a message and redirect
 *
 * @param string $link. The URL to redirect to
 * @param string $message to be print
 * @param string $type of message (success, info, warning, error). Default = info
 * @return void
 */
function vpl_redirect($link, $message, $type = 'info', $errorcode='') {
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
 * @codeCoverageIgnore
 *
 * Inmediate redirect
 *
 * @param string $url URL to redirect to
 * @return void
 */
function vpl_inmediate_redirect($url) {
    global $OUTPUT;
    if (! mod_vpl::header_is_out()) {
        echo $OUTPUT->header();
    }
    static $idcount = 0;
    $idcount ++;
    $text = '<div class="continuebutton"><a id="vpl_red' . $idcount . '" href="';
    $text .= $url. '">' . get_string( 'continue' ) . '</a></div>';
    $deco = urldecode( $url);
    $deco = html_entity_decode( $deco );
    echo vpl_include_js( 'window.location.replace("' . $deco . '");' );
    echo $text;
    echo $OUTPUT->footer();
    die();
}
/**
 * @codeCoverageIgnore
 *
 * Set JavaScript file from subdir jscript to be load
 *
 * @param $file string
 *            name of file to load
 * @param $defer boolean
 *            optional set if the load is inmediate or deffered
 * @return void
 */
function vpl_include_jsfile($file, $defer = true) {
    global $PAGE;
    $PAGE->requires->js( new moodle_url( '/mod/vpl/jscript/' . $file ), ! $defer );
}

/**
 * @codeCoverageIgnore
 *
 * Set JavaScript code to be included
 *
 * @param $jscript string
 *            JavaScript code
 * @return void
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
 * @codeCoverageIgnore
 *
 * Popup message box to show text
 *
 * @param string $text to show. It use s() to sanitize text
 * @param boolean $print or not
 * @return void
 */
function vpl_js_alert($text, $print = true) {
    $aux = addslashes( $text ); // Sanitize text.
    $aux = str_replace( "\n", "\\n", $aux ); // Add \n to show multiline text.
    $aux = str_replace( "\r", "", $aux ); // Remove \r.
    $ret = vpl_include_js( 'alert("' . $aux . '");' );
    if ($print) {
        echo $ret;
        @ob_flush();
        flush();
    } else {
        return $ret;
    }
}

/**
 * @codeCoverageIgnore
 */
function vpl_get_select_time($maximum = null) {
    $minute = 60;
    if ($maximum === null) { // Default value.
        $maximum = 35 * $minute;
    }
    $ret = array (
            0 => get_string( 'select' )
    );
    if ($maximum <= 0) {
        return $ret;
    }
    $value = 4;
    if ($maximum < $value) {
        $value = $maximum;
    }
    while ( $value <= $maximum ) {
        if ($value < $minute) {
            $ret[$value] = get_string( 'numseconds', '', $value );
        } else {
            $num = ( int ) ($value / $minute);
            $ret[$num * $minute] = get_string( 'numminutes', '', $num );
            $value = $num * $minute;
        }
        $value *= 2;
    }
    return $ret;
}

/**
 * @codeCoverageIgnore
 *
 * Converts a size in byte to string in Kb, Mb, Gb and Tb.
 * Follows IEC "Prefixes for binary multiples".
 *
 * @param int $size Size in bytes
 *
 * @return string
 */
function vpl_conv_size_to_string($size) {
    static $measure = array (
            1024,
            1048576,
            1073741824,
            1099511627776,
            PHP_INT_MAX
    );
    static $measurename = array (
            'KiB',
            'MiB',
            'GiB',
            'TiB'
    );
    for ($i = 0; $i < count( $measure ) - 1; $i ++) {
        if ($measure[$i] <= 0) { // Check for int overflow.
            $num = $size / $measure[$i - 1];
            return sprintf( '%.2f %s', $num, $measurename[$i - 1] );
        }
        if ($size < $measure[$i + 1]) {
            $num = $size / $measure[$i];
            if ($num >= 3 || $size % $measure[$i] == 0) {
                return sprintf( '%4d %s', $num, $measurename[$i] );
            } else {
                return sprintf( '%.2f %s', $num, $measurename[$i] );
            }
        }
    }
}

/**
 * @codeCoverageIgnore
 *
 * Return the array key after or equal to value
 *
 * @param $array
 * @param int $value of key to search >=
 * @return int key found
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
 * @codeCoverageIgnore
 *
 * Returns un array with the format [size in bytes] => size in text.
 * The first element is [0] => select.
 *
 * @param int $minimum the initial value
 * @param int $maximum the limit of values generates
 *
 * @return array Key value => Text value
 */
function vpl_get_select_sizes(int $minimum = 0, int $maximum = PHP_INT_MAX): array {
    $maximum = ( int ) $maximum;
    if ($maximum < 0) {
        $maximum = PHP_INT_MAX;
    }
    if ($maximum > 17.0e9) {
        $maximum = 16 * 1073741824;
    }
    $ret = array (
            0 => get_string( 'select' )
    );
    if ($minimum > 0) {
        $value = $minimum;
    } else {
        $value = 256 * 1024;
    }
    $pre = 0;
    $increment = $value / 4;
    while ( $value <= $maximum && $value > 0 ) { // Avoid int overflow.
        $ret[$value] = vpl_conv_size_to_string( $value );
        $pre = $value;
        $value += $increment;
        $value = ( int ) $value;
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
 * @codeCoverageIgnore
 *
 * Detects end of line separator.
 *
 * @param string& $data Text to check.
 *
 * @return string Newline separator "\r\n", "\n", "\r".
 */
function vpl_detect_newline(&$data) {
    // Detect text newline chars.
    if (strrpos( $data, "\r\n" ) !== false) {
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
 * @codeCoverageIgnore
 */
function vpl_notice(string $text, $type = 'success') {
    global $OUTPUT;
    echo $OUTPUT->notification($text, $type);
}

/**
 * @codeCoverageIgnore
 *
 * Remove trailing right zeros from a float as string
 *
 * @param string $value float to remove right zeros
 *
 * @return string
 */
function vpl_rtzeros($value) {
    if (strpos($value, '.') || strpos($value, ',')) {
        return rtrim(rtrim($value, '0'), '.,');
    }
    return $value;
}

/**
 * @codeCoverageIgnore
 *
 * Generate an array with index an values $url.index
 *
 * @param string $url base
 * @param $array array of index
 * @return array with index as key and url as value
 */
function vpl_select_index($url, $array) {
    $ret = array ();
    foreach ($array as $value) {
        $ret[$value] = $url . $value;
    }
    return $ret;
}

/**
 * @codeCoverageIgnore
 *
 * Generate an array ready to be use in $OUTPUT->select_url
 *
 * @param string $url base
 * @param array $array of values
 * @return array with url as key and text as value
 */
function vpl_select_array($url, $array) {
    $ret = array ();
    foreach ($array as $value) {
        $ret[$url . $value] = get_string( $value, VPL );
    }
    return $ret;
}

/**
 * @codeCoverageIgnore
 */
function vpl_fileextension($filename) {
    return pathinfo( $filename, PATHINFO_EXTENSION );
}


/**
 * @codeCoverageIgnore
 *
 * Get if filename has image extension
 * @param string $filename
 * @return boolean
 */
function vpl_is_image($filename) {
    return preg_match( '/^(gif|jpg|jpeg|png|ico)$/i', vpl_fileextension( $filename ) ) == 1;
}

/**
 * @codeCoverageIgnore
 *
 * Get if filename has binary extension or binary data
 * @param string $filename
 * @param string &$data file contents
 * @return boolean
 */
function vpl_is_binary($filename, &$data = false) {
    if ( vpl_is_image( $filename ) ) {
        return true;
    }
    $fileext = 'zip|jar|pdf|tar|bin|7z|arj|deb|gzip|';
    $fileext .= 'rar|rpm|dat|db|dll|rtf|doc|docx|odt|exe|com';
    if ( preg_match( '/^(' . $fileext . ')$/i', vpl_fileextension( $filename ) ) == 1 ) {
        return true;
    }
    if ($data === false) {
        return false;
    }
    return mb_detect_encoding( $data, 'UTF-8', true ) != 'UTF-8';
}

/**
 * @codeCoverageIgnore
 *
 * Return data encoded to base64
 * @param string $filename
 * @param string &$data file contents
 * @return string
 */
function vpl_encode_binary($filename, &$data) {
    return base64_encode( $data );
}

/**
 * @codeCoverageIgnore
 *
 * Return data decoded from base64
 * @param string $filename
 * @param string &$data file contents
 * @return string
 */
function vpl_decode_binary($filename, $data) {
    return base64_decode( $data );
}

/**
 * @codeCoverageIgnore
 *
 * Return if path is valid
 * @param string $path
 * @return boolean
 */
function vpl_is_valid_path_name($path) {
    if (strlen( $path ) > 256) {
        return false;
    }
    $dirs = explode( '/', $path );
    for ($i = 0; $i < count( $dirs ); $i ++) {
        if (! vpl_is_valid_file_name( $dirs[$i] )) {
            return false;
        }
    }
    return true;
}

/**
 * @codeCoverageIgnore
 *
 * Return if file or directory name is valid
 * @param string $name
 * @return boolean
 */
function vpl_is_valid_file_name($name) {
    $backtick = chr( 96 ); // Avoid warnning in codecheck.
    $regexp = '/[\x00-\x1f]|[:-@]|[{-~]|\\\\|\[|\]|[\/\^';
    $regexp .= $backtick . '´]|^\-|^ | $|^\.$|^\.\.$/';
    if (strlen( $name ) < 1) {
        return false;
    }
    if (strlen( $name ) > 128) {
        return false;
    }
    return preg_match( $regexp, $name ) === 0;
}

/**
 * @codeCoverageIgnore
 *
 * Truncate string to the limit passed
 * @param string &$string
 * @param int $limit
 */
function vpl_truncate_string(&$string, $limit) {
    if (strlen( $string ) <= $limit) {
        return;
    }
    $string = substr( $string, 0, $limit - 3 ) . '...';
}

/**
 * @codeCoverageIgnore
 */
function vpl_bash_export($var, $value) {
    if ( is_int($value) ) {
        return 'export ' . $var . '=' . $value . "\n";
    } else {
        return 'export ' . $var . "='" . str_replace( "'", "'\"'\"'", $value ) . "'\n";
    }
}

/**
 * @codeCoverageIgnore
 *
 * For debug purpose
 * Return content of vars ready to HTML
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
 * @codeCoverageIgnore
 *
 * Truncate string fields of the VPL table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_vpl($instance) {
    if (isset($instance->password)) {
        $instance->password = trim($instance->password);
    }
    foreach (['name', 'requirednet', 'password', 'variationtitle'] as $field) {
        if (isset($instance->$field)) {
            vpl_truncate_string( $instance->$field, 255 );
        }
    }
}

/**
 * @codeCoverageIgnore
 *
 * Truncate string fields of the variations table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_variations($instance) {
    vpl_truncate_string( $instance->identification, 40 );
}

/**
 * @codeCoverageIgnore
 *
 * Truncate string fields of the running_processes table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_running_processes($instance) {
    vpl_truncate_string( $instance->server, 255 );
}

/**
 * @codeCoverageIgnore
 *
 * Truncate string fields of the jailservers table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_jailservers($instance) {
    vpl_truncate_string( $instance->laststrerror, 255 );
    vpl_truncate_string( $instance->server, 255 );
}

/**
 * @codeCoverageIgnore
 *
 * Check if IP is within networks
 *
 * @param $networks string with conma separate networks
 * @param $ip string optional with the IP to check, if omited then remote IP
 *
 * @return boolean true found
 */
function vpl_check_network($networks, $ip = false) {
    $networks = trim($networks);
    if ($networks == '') {
        return true;
    }
    if ($ip === false) {
        $ip = getremoteaddr();
    }
    return address_in_subnet( $ip, $networks );
}

/**
 * @codeCoverageIgnore
 *
 * Get awesome icon for action
 * @param String $id
 * @return string
 */
function vpl_get_awesome_icon($str, $classes = '') {
    $icon = 'mod_vpl:' . $str;
    $imap = mod_vpl_get_fontawesome_icon_map();
    if ( isset( $imap[$icon]) ) {
        $ficon = $imap[$icon];
        return '<i class="fa ' . $ficon . $classes . '"></i> ';
    }
    return '';
}


/**
 * @codeCoverageIgnore
 *
 * Create a new tabobject for navigation
 * @param String $id
 * @param string|moodle_url $href
 * @param string $str to be i18n
 * @param string $comp component
 * @return tabobject
 */
function vpl_create_tabobject($id, $href, $str, $comp = 'mod_vpl') {
    $stri18n = get_string( $str, $comp);
    $strdescription = vpl_get_awesome_icon($str) . $stri18n;
    return new tabobject( $id, $href, $strdescription, $stri18n );
}

/**
 * @codeCoverageIgnore
 *
 * Get version string
 * @return string
 */
function vpl_get_version() {
    static $version = '';
    if ($version === '' && false) { // Removed version information.
        $plugin = new stdClass();
        require_once(dirname( __FILE__ ) . '/version.php');
        $version = $plugin->release;
    }
    return $version;
}

/**
 * @codeCoverageIgnore
 *
 * Polyfill for getting user picture fields
 * @return string List of fields separated by "," u.field
 */
function vpl_get_picture_fields() {
    if (method_exists('\core_user\fields', 'get_picture_fields')) {
        return 'u.' . implode(',u.', \core_user\fields::get_picture_fields());
    } else {
        return user_picture::fields( 'u' );
    }
}

/**
 * @codeCoverageIgnore
 */
function vpl_get_webservice_available() {
    global $DB, $USER, $CFG;
    if ($USER->id <= 2) {
        return false;
    }
    if (! $CFG->enablewebservices) {
        return false;
    }
    $service = $DB->get_record( 'external_services', array (
            'shortname' => 'mod_vpl_edit',
            'enabled' => 1
    ) );
    return ! empty( $service );
}

/**
 * @codeCoverageIgnore
 */
function vpl_get_webservice_token($vpl) {
    global $DB, $USER, $CFG;
    $now = time();
    if ($USER->id <= 2) {
        return '';
    }
    if (! $CFG->enablewebservices) {
        return '';
    }
    $service = $DB->get_record( 'external_services', array (
            'shortname' => 'mod_vpl_edit',
            'enabled' => 1
    ) );
    if (empty( $service )) {
        return '';
    }
    $tokenrecord = $DB->get_record( 'external_tokens', array (
            'sid' => session_id(),
            'userid' => $USER->id,
            'externalserviceid' => $service->id
    ) );
    if (! empty( $tokenrecord ) && $tokenrecord->validuntil < $now) {
        unset( $tokenrecord ); // Will be delete before creating a new one.
    }
    if (empty( $tokenrecord )) {
        // Remove old tokens from DB.
        $select = 'validuntil > 0 AND  validuntil < ?';
        $DB->delete_records_select( 'external_tokens', $select, array (
                $now
        ) );
        // Generate unique token.
        for ($i = 0; $i < 100; $i ++) {
            $token = md5( uniqid( mt_rand(), true ) );
            $tokenrecord = $DB->get_record( 'external_tokens', array (
                    'token' => $token
            ) );
            if (empty( $tokenrecord )) {
                break;
            }
        }
        if ($i >= 100) {
            return '';
        }
        $tokenrecord = new stdClass();
        $tokenrecord->token = $token;
        $tokenrecord->sid = session_id();
        $tokenrecord->userid = $USER->id;
        $tokenrecord->creatorid = $USER->id;
        $tokenrecord->tokentype = EXTERNAL_TOKEN_EMBEDDED;
        $tokenrecord->timecreated = $now;
        $tokenrecord->validuntil = $now + DAYSECS;
        $tokenrecord->iprestriction = getremoteaddr();
        $tokenrecord->contextid = $vpl->get_context()->id;
        $tokenrecord->externalserviceid = $service->id;
        $DB->insert_record( 'external_tokens', $tokenrecord );
    }
    return $tokenrecord->token;
}

/**
 * @codeCoverageIgnore
 */
function vpl_get_webservice_urlbase($vpl) {
    global $CFG;
    $token = vpl_get_webservice_token( $vpl );
    if ($token == '') {
        return '';
    }
    return $CFG->wwwroot . '/mod/vpl/webservice.php?moodlewsrestformat=json'
           . '&wstoken=' . $token . '&id=' . $vpl->get_course_module()->id . '&wsfunction=';
}

/**
 * @codeCoverageIgnore
 * Agregate usersids and groupsids of array of objects of override assigned records
 * @param array $overridesseparated of objects of records
 * @return array
 */
function vpl_agregate_overrides($overridesseparated) {
    $usersids = [];
    $groupids = [];
    foreach ($overridesseparated as $override) {
        if (!isset($usersids[$override->id])) {
            $usersids[$override->id] = [];
            $groupids[$override->id] = [];
        }
        if (!empty($override->usersids)) {
            array_push($usersids[$override->id], $override->usersids);
        }
        if (!empty($override->groupids)) {
            array_push($groupids[$override->id], $override->groupids);
        }
    }
    $overrides = [];
    foreach ($overridesseparated as $override) {
        if (!isset($overrides[$override->id])) {
            $override->usersids = implode(',', $usersids[$override->id]);
            $override->groupids = implode(',', $groupids[$override->id]);
            $overrides[$override->id] = $override;
        }
    }
    return $overrides;
}

/**
 * Calls a function with lock.
 * @param string $locktype Name of the lock type (unique)
 * @param string $resource Name of the resourse (unique)
 * @param string $function Name of the function to call
 * @param array $parms Parameters to pass to the function
 * @return mixed Value returned by the function or throw exception
 */
function vpl_call_with_lock(string $locktype, string $resource, string $function, array $parms) {
    $lockfactory = \core\lock\lock_config::get_lock_factory($locktype);
    if ($lock = $lockfactory->get_lock($resource, VPL_LOCK_TIMEOUT)) {
        try {
            $result = $function(...$parms);
            $lock->release();
            return $result;
        } catch (Exception $e) {
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
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
}
