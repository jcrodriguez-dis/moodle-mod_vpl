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
 * Show two files diff
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/diff.class.php';

require_login();

$id = required_param('id', PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('similarity/diff.php', array('id' => $id));
//Print header
$PAGE->requires->css(new moodle_url('/mod/vpl/css/sh.css'));
$vpl->print_header_simple(get_string('diff',VPL));
//$vpl->print_view_tabs(basename(__FILE__));
//Get left file
vpl_diff::vpl_get_similfile('1',$vpl,$HTMLheader1,$filename1,$data1);
//Get right file
vpl_diff::vpl_get_similfile('2',$vpl,$HTMLheader2,$filename2,$data2);
//Show files
vpl_diff::show($filename1,$data1,$HTMLheader1,$filename2,$data2,$HTMLheader2);
$vpl->print_footer();

