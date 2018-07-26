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
define( 'VPL_GRADE_CAPABILITY', 'mod/vpl:grade' );
define( 'VPL_VIEW_CAPABILITY', 'mod/vpl:view' );
define( 'VPL_SUBMIT_CAPABILITY', 'mod/vpl:submit' );
define( 'VPL_SIMILARITY_CAPABILITY', 'mod/vpl:similarity' );
define( 'VPL_ADDINSTANCE_CAPABILITY', 'mod/vpl:addinstance' );
define( 'VPL_SETJAILS_CAPABILITY', 'mod/vpl:setjails' );
define( 'VPL_MANAGE_CAPABILITY', 'mod/vpl:manage' );

require_once(dirname(__FILE__).'/vpl.class.php');

/**
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
    if (isset( $SESSION->$fullname )) { // Exists var?
        $res = $SESSION->$fullname;
    }
    $res = optional_param( $parname, $res, PARAM_ALPHA );
    $SESSION->$fullname = $res;
    return $res;
}

/**
 * Open/create a file and its dir
 *
 * @param $filename path
 *            to file
 * @return file descriptor
 */
function vpl_fopen($filename) {
    global $CFG;

    if (! file_exists( $filename )) { // Exists file?
        $dir = dirname( $filename );
        if (! file_exists( $dir )) { // Create dir?
            if (! mkdir( $dir, $CFG->directorypermissions, true ) ) {
                throw new file_exception('storedfileproblem', 'Error creating a directory to save files in VPL');
            }
        }
    }
    $fp = fopen( $filename, 'wb+' );
    if ($fp === false) {
        throw new file_exception('storedfileproblem', 'Error creating file in VPL');
    }
    return $fp;
}

/**
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
                    $list [] = $name;
                }
            }
            closedir( $dd );
            $ret = true;
            foreach ($list as $name) {
                $ret = vpl_delete_dir( $dirname . '/' . $name ) and $ret;
            }
            $ret = rmdir( $dirname ) and $ret;
        } else {
            $ret = unlink( $dirname );
        }
    }
    return $ret;
}

/**
 * Outputs a zip file and removes it. Must be called before any other output
 *
 * @param $zipfilename name of the file with the data
 * @param $name of file to be shown, without '.zip'
 *
 */
function vpl_output_zip($zipfilename, $name) {
    if (! file_exists($zipfilename)) {
        print_error("Zip file not found");
        die;
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
            $lang = $parts [0];
        }
        if (isset( $commonlangs [$lang] )) {
            $lang = $lang . '_' . $commonlangs [$lang];
        }
        $lang .= '.UTF-8';
    }
    return $lang;
}

/**
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
    $href = $CFG->wwwroot . $parms [0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $href .= ($p > 1 ? '&amp;' : '?') . urlencode( $parms [$p] ) . '=' . urlencode( $parms [$p + 1] );
    }
    return $href;
}

/**
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
    $href = $CFG->wwwroot . '/mod/vpl/' . $parms [0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $href .= ($p > 1 ? '&amp;' : '?') . urlencode( $parms [$p] ) . '=' . urlencode( $parms [$p + 1] );
    }
    return $href;
}

/**
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
    $url = $parms [0];
    for ($p = 1; $p < $l - 1; $p += 2) {
        $url .= ($p > 1 ? '&amp;' : '?') . urlencode( $parms [$p] ) . '=' . urlencode( $parms [$p + 1] );
    }
    return $url;
}
/**
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
 * Print a message and redirect
 *
 * @param $link URL
 *            to redirect to
 * @param $message string
 *            to be print
 * @param $type string
 *            type of message (success,info,warning,error). default = info
 * @return void
 */
function vpl_redirect($link, $message, $type = 'info', $errorcode='') {
    global $OUTPUT;
    global $CFG;
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
 * @param $link URL
 *            to redirect to
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
 * Popup message box to show text
 *
 * @param $text text
 *            to show. It use s() to sanitize text
 * @param
 *            $print
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
            $ret [$value] = get_string( 'numseconds', '', $value );
        } else {
            $num = ( int ) ($value / $minute);
            $ret [$num * $minute] = get_string( 'numminutes', '', $num );
            $value = $num * $minute;
        }
        $value *= 2;
    }
    return $ret;
}

/**
 * Return the post_max_size PHP config option in bytes
 *
 * @return int max size in bytes
 */
function vpl_get_max_post_size() {
    $maxs = trim( ini_get( 'post_max_size' ) );
    $len = strlen( $maxs );
    $last = strtolower( $maxs [$len - 1] );
    $max = ( int ) substr( $maxs, 0, $len - 1 );
    if ($last == 'k') {
        $max *= 1024;
    } else if ($last == 'm') {
        $max *= 1024 * 1024;
    } else if ($last == 'g') {
        $max *= 1024 * 1024 * 1000;
    }
    return $max;
}

/**
 * Convert a size in byte to string in Kb, Mb, Gb and Tb Following IEC "Prefixes for binary multiples"
 *
 * @param $size int
 *            size in bytes
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
        if ($measure [$i] <= 0) { // Check for int overflow.
            $num = $size / $measure [$i - 1];
            return sprintf( '%.2f %s', $num, $measurename [$i - 1] );
        }
        if ($size < $measure [$i + 1]) {
            $num = $size / $measure [$i];
            if ($num >= 3 || $size % $measure [$i] == 0) {
                return sprintf( '%4d %s', $num, $measurename [$i] );
            } else {
                return sprintf( '%.2f %s', $num, $measurename [$i] );
            }
        }
    }
}

/**
 * Return the array key after or equal to value
 *
 * @param
 *            $array
 * @param $value of
 *            key to search
 * @return key found
 */
function vpl_get_array_key($array, $value) {
    foreach ($array as $key => $nothing) {
        if ($key >= $value) {
            return $key;
        }
    }
    return $key;
}

/**
 * Return un array with the format [size in bytes]=> size in text The first element is [0] => select
 *
 * @param $minimum the
 *            initial value
 * @param $maximum the
 *            limit of values generates
 * @return array
 */
function vpl_get_select_sizes($minimum = 0, $maximum = PHP_INT_MAX) {
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
        $ret [$value] = vpl_conv_size_to_string( $value );
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
        $ret [$maximum] = vpl_conv_size_to_string( $maximum );
    }
    return $ret;
}

/**
 *
 * @param string $data
 * @return string newline separator "\r\n", "\n", "\r"
 */
function vpl_detect_newline(&$data) {
    // Detect text newline chars.
    if (strrpos( $data, "\r\n" ) !== false) {
        return "\r\n"; // Windows.
    } else if (strrpos( $data, "\n" ) !== false) {
        return "\n"; // UNIX.
    } else if (strrpos( $data, "\r" ) !== false) {
        return "\r"; // Mac.
    } else {
        return "\n"; // Default Unix.
    }
}

function vpl_notice($text, $type = 'success') {
    global $OUTPUT;
    echo $OUTPUT->notification( $text, $type );
}

/**
 * Remove trailing zeros from a float as string
 *
 * @param
 *            $value
 * @return string
 */
function vpl_rtzeros($value) {
    if (strpos( $value, '.' ) || strpos( $value, ',' )) {
        return rtrim( rtrim( $value, '0' ), '.,' );
    }
    return $value;
}

/**
 * Generate an array with index an values $url.index
 *
 * @param $url url
 *            base
 * @param $array array
 *            of index
 * @return array with index as key and url as value
 */
function vpl_select_index($url, $array) {
    $ret = array ();
    foreach ($array as $value) {
        $ret [$value] = $url . $value;
    }
    return $ret;
}

/**
 * Generate an array ready to be use in $OUTPUT->select_url
 *
 * @param $url url
 *            base
 * @param $array array
 *            of values
 * @return array with url as key and text as value
 */
function vpl_select_array($url, $array) {
    $ret = array ();
    foreach ($array as $value) {
        $ret [$url . $value] = get_string( $value, VPL );
    }
    return $ret;
}
function vpl_fileextension($filename) {
    return pathinfo( $filename, PATHINFO_EXTENSION );
}

function vpl_is_image($filename) {
    return preg_match( '/^(gif|jpg|jpeg|png|ico)$/i', vpl_fileextension( $filename ) ) == 1;
}

function vpl_is_binary($filename, &$data = false) {
    if ( vpl_is_image( $filename ) ) {
        return true;
    }
    $fileext = 'zip|jar|pdf|tar|bin|7z|arj|deb|gzip|';
    $fileext .= 'rar|rpm|dat|db|rtf|doc|docx|odt';
    if ( preg_match( '/^(' . $fileext . ')$/i', vpl_fileextension( $filename ) ) == 1 ) {
        return true;
    }
    if ($data === false) {
        return false;
    }
    return mb_detect_encoding( $data, 'UTF-8', true ) != 'UTF-8';
}
function vpl_encode_binary($filename, &$data) {
    return base64_encode( $data );
}
function vpl_decode_binary($filename, $data) {
    return base64_decode( $data );
}
function vpl_is_valid_path_name($path) {
    if (strlen( $path ) > 256) {
        return false;
    }
    $dirs = explode( '/', $path );
    for ($i = 0; $i < count( $dirs ); $i ++) {
        if (! vpl_is_valid_file_name( $dirs [$i] )) {
            return false;
        }
    }
    return true;
}
function vpl_is_valid_file_name($filename) {
    $backtick = chr( 96 ); // Avoid warnning in codecheck.
    $regexp = '/[\x00-\x1f]|[:-@]|[{-~]|\\\\|\[|\]|[\/\^';
    $regexp .= $backtick . '´]|^\-|^ | $|^\.$|^\.\.$/';
    if (strlen( $filename ) < 1) {
        return false;
    }
    if (strlen( $filename ) > 128) {
        return false;
    }
    return preg_match( $regexp, $filename ) === 0;
}
function vpl_truncate_string(&$string, $limit) {
    if (strlen( $string ) <= $limit) {
        return;
    }
    $string = substr( $string, 0, $limit - 3 ) . '...';
}

function vpl_bash_export($var, $value) {
    if ( is_int($value) ) {
        return 'export ' . $var . '=' . $value . "\n";
    } else {
        return 'export ' . $var . "='" . str_replace( "'", "'\"'\"'", $value ) . "'\n";
    }
}

/**
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
 * Truncate string fields of the VPL table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_vpl($instance) {
    vpl_truncate_string( $instance->name, 255 );
    vpl_truncate_string( $instance->requirednet, 255 );
    vpl_truncate_string( $instance->password, 255 );
    vpl_truncate_string( $instance->variationtitle, 255 );
}

/**
 * Truncate string fields of the variations table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_variations($instance) {
    vpl_truncate_string( $instance->identification, 40 );
}

/**
 * Truncate string fields of the running_processes table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_running_processes($instance) {
    vpl_truncate_string( $instance->server, 255 );
}

/**
 * Truncate string fields of the jailservers table
 * @param $instance object with the record
 * @return void
 */
function vpl_truncate_jailservers($instance) {
    vpl_truncate_string( $instance->laststrerror, 255 );
    vpl_truncate_string( $instance->server, 255 );
}

/**
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
 * Get version string
 * @return string
 */
function vpl_get_version() {
    static $version = '';
    if (! isset( $version )) {
        $plugin = new stdClass();
        require_once(dirname( __FILE__ ) . '/version.php');
        $version = $plugin->release;
    }
    return $version;
}

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
function vpl_get_webservice_token($vpl) {
    global $DB, $SESSION, $USER, $CFG;
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
    if (! empty( $tokenrecord ) and $tokenrecord->validuntil < $now) {
        unset( $tokenrecord ); // Will be delete before creating a new one.
    }
    if (empty( $tokenrecord )) {
        // Remove old tokens from DB.
        $select = 'validuntil > 0 AND  validuntil < ?';
        $DB->delete_records_select( 'external_tokens', $select, array (
                $now
        ) );
        // Select unique token.
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
function vpl_get_webservice_urlbase($vpl) {
    global $CFG;
    $token = vpl_get_webservice_token( $vpl );
    if ($token == '') {
        return '';
    }
    return $CFG->wwwroot . '/mod/vpl/webservice.php?moodlewsrestformat=json'
           . '&wstoken=' . $token . '&id=' . $vpl->get_course_module()->id . '&wsfunction=';
}
