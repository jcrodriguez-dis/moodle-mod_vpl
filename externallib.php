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
 * @package mod_vpl
 * @copyright 2014 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

// TODO Organize security checks.

defined( 'MOODLE_INTERNAL' ) || die();


require_once(dirname( __FILE__ ) . '/../../lib/externallib.php');
require_once(dirname( __FILE__ ) . '/locallib.php');
require_once(dirname( __FILE__ ) . '/forms/edit.class.php');
require_once(dirname( __FILE__ ) . '/vpl_submission.class.php');

/**
 * @codeCoverageIgnore
 */
class mod_vpl_webservice extends external_api {
    /**
     * Returns VPL activity object for coursemodule id.
     * Does checks fro required netwok and password if setted.
     *
     * @param int $id The coursemodule id.
     * @param string $pssword The password for using the VPL activity.
     * @return \mod_vpl object or throws exception if not available.
     */
    private static function initial_checks($id, $password) {
        $vpl = new mod_vpl( $id );
        if (! $vpl->has_capability( VPL_GRADE_CAPABILITY )) {
            if (! $vpl->pass_network_check()) {
                $message = get_string( 'opnotallowfromclient', VPL ) . ' ' . getremoteaddr();
                throw new Exception( $message );
            }
            if (! $vpl->pass_password_check( $password )) {
                throw new Exception( get_string( 'requiredpassword', VPL ) );
            }
        }
        return $vpl;
    }

    /**
     * Encode file format from array if key = value (filename => data),
     * to an array of arrays with 'name', 'data' and 'enconding' for each file.
     *
     * @param array $files of (filename => data).
     * @return array of array with 'name', 'data' and 'conding' keys.
     */
    private static function encode_files(&$oldfiles) {
        $files = [];
        foreach ($oldfiles as $name => $data) {
            $file = [];
            $file['name'] = $name;
            if ( vpl_is_binary($name, $data) ) {
                $file['data'] = base64_encode( $data );
                $file['encoding'] = 1;
            } else {
                $file['data'] = $data;
                $file['encoding'] = 0;
            }
            $files[] = $file;
        }
        return $files;
    }
    /**
     * Revert encode_files action.
     *
     * @param array of array with 'name', 'data' and 'conding' keys.
     * @return array $files of (filename => data).
     */
    private static function decode_files(&$oldfiles) {
        $files = [];
        foreach ($oldfiles as $file) {
            $name = $file['name'];
            if ( isset($file['encoding']) && $file['encoding'] == 1 ) {
                $data = base64_decode( $file['data'] );
            } else {
                $data = $file['data'];
            }
            $files[$name] = $data;
        }
        return $files;
    }
    /*
     * info function. return information of the activity
     */
    public static function info_parameters() {
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' )
        ), 'Parameters', VALUE_REQUIRED);
    }
    public static function info($id, $password) {
        self::validate_parameters( self::info_parameters(), array (
                'id' => $id,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_VIEW_CAPABILITY );
        if (! $vpl->is_visible()) {
            throw new Exception( get_string( 'notavailable' ) );
        }
        $instance = $vpl->get_instance();
        $ret = array (
                'name' => $instance->name,
                'shortdescription' => $instance->shortdescription,
                'intro' => $instance->intro,
                'introformat' => ( int ) $instance->introformat,
                'reqpassword' => ($instance->password > '' ? 1 : 0),
                'example' => ( int ) $instance->example,
                'restrictededitor' => ( int ) $instance->restrictededitor,
                'maxfiles' => ( int ) $instance->maxfiles,
                'reqfiles' => array ()
        );
        $files = mod_vpl_edit::get_requested_files( $vpl );
        // Adapt array of name => value content to format array of objects {name, data}.
        $files = self::encode_files( $files );
        $ret['reqfiles'] = $files;
        return $ret;
    }
    public static function info_returns() {
        return new external_single_structure( array (
                'name' => new external_value( PARAM_TEXT, 'Name', VALUE_REQUIRED),
                'shortdescription' => new external_value( PARAM_TEXT, 'Short description', VALUE_REQUIRED),
                'intro' => new external_value( PARAM_RAW, 'Full description', VALUE_REQUIRED),
                'introformat' => new external_value( PARAM_INT, 'Description format', VALUE_REQUIRED ),
                'reqpassword' => new external_value( PARAM_INT, 'Activity requiere password', VALUE_REQUIRED),
                'example' => new external_value( PARAM_INT, 'Activity is an example', VALUE_REQUIRED),
                'restrictededitor' => new external_value( PARAM_INT, 'Activity edition is restricted', VALUE_REQUIRED),
                'maxfiles' => new external_value( PARAM_INT, 'Maximum number of file acepted', VALUE_REQUIRED),
                'reqfiles' => new external_multiple_structure( new external_single_structure( array (
                        'name' => new external_value( PARAM_TEXT, 'File name', VALUE_REQUIRED),
                        'data' => new external_value( PARAM_RAW, 'File content', VALUE_REQUIRED),
                        'encoding' => new external_value( PARAM_INT, 'File enconding 1 => B64', VALUE_DEFAULT, 0)
                ) ), 'Required files', VALUE_REQUIRED)
                ), 'Parameters', VALUE_REQUIRED);
    }

    /*
     * save function. save/submit the students files
     */
    public static function save_parameters() {
        $descuserid = 'User ID to use (required mod/vpl:manage capability)';
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED),
                'files' => new external_multiple_structure( new external_single_structure( array (
                        'name' => new external_value( PARAM_RAW, 'File name', VALUE_REQUIRED),
                        'data' => new external_value( PARAM_RAW, 'File content', VALUE_REQUIRED),
                        'encoding' => new external_value( PARAM_INT, 'File enconding 1 => B64', VALUE_DEFAULT, 0)
                ) ), 'Files', VALUE_REQUIRED),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' ),
                'userid' => new external_value( PARAM_INT, $descuserid , VALUE_DEFAULT, -1 )
        ), 'Parameters', VALUE_REQUIRED );
    }
    public static function save($id, $files = array(), $password = '', $userid = -1) {
        global $USER;
        self::validate_parameters( self::save_parameters(), array (
                'id' => $id,
                'files' => $files,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        if ($userid == -1) {
            $userid = $USER->id;
            $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
            if (! $vpl->is_submit_able()) {
                throw new Exception( get_string( 'notavailable' ) );
            }
        } else {
            $vpl->require_capability( VPL_MANAGE_CAPABILITY );
        }
        $instance = $vpl->get_instance();
        if ($instance->example || ($instance->restrictededitor && ! $vpl->has_capability(VPL_MANAGE_CAPABILITY))) {
            throw new Exception( get_string( 'notavailable' ) );
        }
        // Adapts to the file format VPL3.2.
        $files = self::decode_files($files);
        mod_vpl_edit::save( $vpl, $userid, $files );
    }

    public static function save_returns() {
        return null;
    }

    /*
     * open function. return the student's submitted files
     */
    public static function open_parameters() {
        $descuserid = 'User ID to use (required mod/vpl:grade capability)';
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' ),
                'userid' => new external_value( PARAM_INT, $descuserid, VALUE_DEFAULT, -1 )
        ), 'Parameters', VALUE_REQUIRED );
    }
    public static function open($id, $password = '', $userid = -1) {
        global $USER;
        self::validate_parameters( self::open_parameters(), [
                'id' => $id,
                'password' => $password,
                'userid' => $userid
        ] );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_VIEW_CAPABILITY );
        if ($userid == -1) {
            $userid = $USER->id;
            if (! $vpl->is_visible()) {
                throw new Exception( get_string( 'notavailable' ) );
            }
        } else {
            $vpl->require_capability( VPL_GRADE_CAPABILITY );
        }
        $compilationexecution = new stdClass();
        $files = mod_vpl_edit::get_submitted_files( $vpl, $userid, $compilationexecution );
        // Adapt array of name => value content to format array of objects {name, data, encoding}.
        $files = self::encode_files( $files );
        $ret = [
                'files' => $files,
                'compilation' => '',
                'evaluation' => '',
                'grade' => ''
        ];
        $attributes = ['compilation', 'evaluation', 'grade'];
        foreach ($attributes as $attribute) {
            if (isset($compilationexecution->$attribute)) {
                $ret[$attribute] = $compilationexecution->$attribute;
            }
        }
        return $ret;
    }
    public static function open_returns() {
        return new external_single_structure( array (
                'files' => new external_multiple_structure( new external_single_structure( array (
                        'name' => new external_value( PARAM_TEXT, 'File name', VALUE_REQUIRED),
                        'data' => new external_value( PARAM_RAW, 'File content', VALUE_REQUIRED),
                        'encoding' => new external_value( PARAM_INT, 'File enconding 1 => B64', VALUE_DEFAULT, 0)
                ) ), 'Files', VALUE_REQUIRED),
                'compilation' => new external_value( PARAM_RAW, 'Compilation result', VALUE_REQUIRED),
                'evaluation' => new external_value( PARAM_RAW, 'Evaluation result', VALUE_REQUIRED),
                'grade' => new external_value( PARAM_RAW, 'Proposed or final grade', VALUE_REQUIRED)
        ), 'Parameters', VALUE_REQUIRED );
    }

    /*
     * evaluate function. evaluate the student's submitted files
     */
    public static function evaluate_parameters() {
        $descuserid = 'User ID to use (required mod/vpl:grade capability)';
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' ),
                'userid' => new external_value( PARAM_INT, $descuserid , VALUE_DEFAULT, -1 )
        ), 'Parameters', VALUE_REQUIRED );
    }
    public static function evaluate($id, $password = ' ', $userid = -1) {
        global $USER;
        self::validate_parameters( self::evaluate_parameters(), array (
                'id' => $id,
                'password' => $password,
                'userid' => $userid
        ) );
        $vpl = self::initial_checks( $id, $password );
        $instance = $vpl->get_instance();
        if ($userid == -1) {
            $userid = $USER->id;
            $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        } else {
            $vpl->require_capability( VPL_GRADE_CAPABILITY );
        }
        if (! $vpl->has_capability(VPL_GRADE_CAPABILITY) ) {
            if (! $vpl->is_visible()) {
                throw new Exception( get_string('notavailable') );
            }
            if (! $vpl->is_submit_able()) {
                throw new Exception( get_string('notavailable') );
            }
            if ($instance->example || ! $instance->evaluate) {
                throw new Exception( get_string('notavailable') );
            }
        }

        $res = mod_vpl_edit::execute( $vpl, $userid, 'evaluate' );
        $monitorurl = 'ws://' . $res->server . ':' . $res->port . '/' . $res->monitorPath;
        $smonitorurl = 'wss://' . $res->server . ':' . $res->securePort . '/' . $res->monitorPath;
        return array ( 'monitorURL' => $monitorurl, 'smonitorURL' => $smonitorurl  );
    }
    public static function evaluate_returns() {
        $desc = "URL to the service that monitor the evaluation in the jail server.
Protocol WebSocket may be ws: or wss: (SSL).
The jail send information as text in this format:
    (message|retrieve|close):(state(:detail)?)?
'message': the jail server reports about the changes to the client.
           With 'state' and optional 'detail?'
'retrieve': the client must get the results of the evaluation
            (call mod_vpl_get_result, the server is waiting).
'close': the conection is to be closed.
if the websocket client send something to the server then the evaluation is stopped.";
        return new external_single_structure( array (
                'monitorURL' => new external_value( PARAM_RAW, $desc, VALUE_REQUIRED),
                'smonitorURL' => new external_value( PARAM_RAW, $desc, VALUE_REQUIRED),
        ), 'Parameters', VALUE_REQUIRED );
    }

    /*
     * get_result function. retrieve the result of the evaluation
     */
    public static function get_result_parameters() {
        $descuserid = 'User ID to use (required mod/vpl:grade capability)';
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' ),
                'userid' => new external_value( PARAM_INT, $descuserid , VALUE_DEFAULT, -1 )
        ) );
    }
    public static function get_result($id, $password = ' ', $userid = -1) {
        global $USER;
        self::validate_parameters( self::get_result_parameters(), array (
                'id' => $id,
                'password' => $password,
                'userid' => $userid
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        $instance = $vpl->get_instance();
        if ($userid == -1) {
            $userid = $USER->id;
            $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        } else {
            $vpl->require_capability( VPL_GRADE_CAPABILITY );
        }
        if (! $vpl->has_capability(VPL_GRADE_CAPABILITY) ) {
            if (! $vpl->is_visible()) {
                throw new Exception( get_string('notavailable') );
            }
            if (! $vpl->is_submit_able()) {
                throw new Exception( get_string('notavailable') );
            }
            if ($instance->example || ! $instance->evaluate) {
                throw new Exception( get_string('notavailable') );
            }
        }
        $compilationexecution = mod_vpl_edit::retrieve_result( $vpl, $userid);
        $ret = [
            'compilation' => '',
            'evaluation' => '',
            'grade' => ''
        ];
        $attributes = ['compilation', 'evaluation', 'grade'];
        foreach ($attributes as $attribute) {
            if (isset($compilationexecution->$attribute)) {
                $ret[$attribute] = $compilationexecution->$attribute;
            }
        }
        return $ret;
    }
    public static function get_result_returns() {
        return new external_single_structure( array (
                'compilation' => new external_value( PARAM_RAW, 'Compilation result', VALUE_REQUIRED),
                'evaluation' => new external_value( PARAM_RAW, 'Evaluation result', VALUE_REQUIRED),
                'grade' => new external_value( PARAM_RAW, 'Proposed or final grade', VALUE_REQUIRED)
        ), 'Parameters', VALUE_REQUIRED);
    }
}
