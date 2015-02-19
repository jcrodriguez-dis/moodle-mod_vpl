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
 * Redirect grade.php
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../config.php';
require_once dirname(__FILE__).'/lib.php';
require_once dirname(__FILE__).'/vpl.class.php';
require_login();
$id=required_param('id', PARAM_INT);
$vpl=new mod_vpl($id);
$vpl->prepare_page('grade.php', array('id' => $id));
$vpl->print_header();
if ($vpl->has_capability(VPL_GRADE_CAPABILITY)) {
    $userid = optional_param('userid',false,PARAM_INT);
    if($userid){
        vpl_inmediate_redirect(vpl_mod_href('forms/gradesubmission.php','id',$id,'userid',$userid));
    }else{
        vpl_inmediate_redirect(vpl_mod_href('views/submissionslist.php','id',$id));
    }
} else {
    vpl_inmediate_redirect(vpl_mod_href('view.php','id',$id));
}
