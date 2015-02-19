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
require_once ($CFG->libdir . "/externallib.php");
require_once dirname( __FILE__ ) . '/locallib.php';
require_once dirname( __FILE__ ) . '/forms/edit.class.php';
require_once dirname( __FILE__ ) . '/vpl_submission.class.php';
class mod_vpl_webservice extends external_api {
    static function initial_checks($id, $password) {
        $vpl = new mod_vpl( $id );
        //No context validation (session is OK)
        //self::validate_context($vpl->get_context());
        if (! $vpl->pass_network_check()) {
            throw new Exception( get_string( 'opnotallowfromclient', VPL ) . ' ' . getremoteaddr() );
        }
        if (! $vpl->pass_password_check( $password )) {
            throw new Exception( get_string( 'requiredpassword', VPL ) );
        }
        return $vpl;
    }
    /*
     * info function. return information of the activity
     */
    public static function info_parameters() {
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' )
        ) );
    }
    public static function info($id, $password) {
        //Parameters validation
        $params = self::validate_parameters( self::info_parameters(), array (
                'id' => $id,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_VIEW_CAPABILITY );
        if (! $vpl->is_visible())
            throw new Exception( get_string( 'notavailable' ) );
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
        //Adapt array[name]=content to array[]=array(name,data)
        $files = mod_vpl_edit::files2object( $files );
        $ret ['reqfiles'] = $files;
        return $ret;
    }
    public static function info_returns() {
        return new external_single_structure( array (
                'name' => new external_value( PARAM_TEXT, 'Name' ),
                'shortdescription' => new external_value( PARAM_TEXT, 'Short description' ),
                'intro' => new external_value( PARAM_RAW, 'Full description' ),
                'introformat' => new external_value( PARAM_INT, 'Description format' ),
                'reqpassword' => new external_value( PARAM_INT, 'Activity requiere password' ),
                'example' => new external_value( PARAM_INT, 'Activity is an example' ),
                'restrictededitor' => new external_value( PARAM_INT, 'Activity edition is restricted' ),
                'maxfiles' => new external_value( PARAM_INT, 'Maximum number of file acepted' ),
                'reqfiles' => new external_multiple_structure( new external_single_structure( array (
                        'name' => new external_value( PARAM_TEXT, 'File name' ),
                        'data' => new external_value( PARAM_RAW, 'File content' )
                ) ) )
        ) );
    }

    /*
     * save function. save/submit the students files
     */
    public static function save_parameters() {
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'files' => new external_multiple_structure( new external_single_structure( array (
                        'name' => new external_value( PARAM_RAW, 'File name' ),
                        'data' => new external_value( PARAM_RAW, 'File content' )
                ) ) ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' )
        ) );
    }
    public static function save($id, $files = array(), $password = '') {
        global $USER;
        //Parameters validation
        $params = self::validate_parameters( self::save_parameters(), array (
                'id' => $id,
                'files' => $files,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        if (! $vpl->is_submit_able())
            throw new Exception( get_string( 'notavailable' ) );
        $instance = $vpl->get_instance();
        if ($instance->example or $instance->restrictededitor)
            throw new Exception( get_string( 'notavailable' ) );
        mod_vpl_edit::save( $vpl, $USER->id, $files );
    }
    public static function save_returns() {
        return null;
    }

    /*
     * open function. return the student's submitted files
     */
    public static function open_parameters() {
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' )
        ) );
    }
    public static function open($id, $password) {
        global $USER;
        //Parameters validation
        $params = self::validate_parameters( self::open_parameters(), array (
                'id' => $id,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_VIEW_CAPABILITY );
        if (! $vpl->is_visible())
            throw new Exception( get_string( 'notavailable' ) );
        $files = mod_vpl_edit::get_submitted_files( $vpl, $USER->id, $CE );
        //Adapt array[name]=content to array[]=array(name,data)
        $files = mod_vpl_edit::files2object( $files );
        $ret = array (
                'files' => $files,
                'compilation' => '',
                'evaluation' => '',
                'grade' => ''
        );
        if ($CE && $vpl->get_instance()->evaluate) {
            $ret ['compilation'] = $CE->compilation;
            $ret ['evaluation'] = $CE->evaluation;
            $ret ['grade'] = $CE->grade;
        }
        return $ret;
    }
    public static function open_returns() {
        return new external_single_structure( array (
                'files' => new external_multiple_structure( new external_single_structure( array (
                        'name' => new external_value( PARAM_TEXT, 'File name' ),
                        'data' => new external_value( PARAM_RAW, 'File content' )
                ) ) ),
                'compilation' => new external_value( PARAM_RAW, 'Compilation result' ),
                'evaluation' => new external_value( PARAM_RAW, 'Evaluation result' ),
                'grade' => new external_value( PARAM_RAW, 'Proposed or final grade' )
        ) );
    }

    /*
     * evaluate function. evaluate the student's submitted files
     */
    public static function evaluate_parameters() {
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' )
        ) );
    }
    public static function evaluate($id, $password) {
        global $USER;
        //Parameters validation
        $params = self::validate_parameters( self::evaluate_parameters(), array (
                'id' => $id,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        $instance = $vpl->get_instance();
        if (! $vpl->is_submit_able())
            throw new Exception( get_string( 'notavailable' ) );
        if ($instance->example or $instance->restrictededitor or ! $instance->evaluate)
            throw new Exception( get_string( 'notavailable' ) );
        $ret = mod_vpl_edit::execute( $vpl, $USER->id, 'evaluate' );
        return array (
                'monitorURL' => $ret->monitorURL
        );
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
                'monitorURL' => new external_value( PARAM_RAW, $desc )
        ) );
    }

    /*
     * get_result function. retrieve the result of the evaluation
     */
    public static function get_result_parameters() {
        return new external_function_parameters( array (
                'id' => new external_value( PARAM_INT, 'Activity id (course_module)', VALUE_REQUIRED ),
                'password' => new external_value( PARAM_RAW, 'Activity password', VALUE_DEFAULT, '' )
        ) );
    }
    public static function get_result($id, $password) {
        global $USER;
        //Parameters validation
        $params = self::validate_parameters( self::get_result_parameters(), array (
                'id' => $id,
                'password' => $password
        ) );
        $vpl = self::initial_checks( $id, $password );
        $vpl->require_capability( VPL_SUBMIT_CAPABILITY );
        $instance = $vpl->get_instance();
        if (! $vpl->is_submit_able())
            throw new Exception( get_string( 'notavailable' ) );
        if ($instance->example or $instance->restrictededitor or ! $instance->evaluate)
            throw new Exception( get_string( 'notavailable' ) );
        $CE = mod_vpl_edit::retrieve_result( $vpl, $USER->id );
        return array (
                'compilation' => $CE->compilation,
                'evaluation' => $CE->evaluation,
                'grade' => $CE->grade
        );
    }
    public static function get_result_returns() {
        return new external_single_structure( array (
                'compilation' => new external_value( PARAM_RAW, 'Compilation result' ),
                'evaluation' => new external_value( PARAM_RAW, 'Evaluation result' ),
                'grade' => new external_value( PARAM_RAW, 'Proposed or final grade' )
        ) );
    }
}
