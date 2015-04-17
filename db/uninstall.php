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
 * Execute post-uninstall custom actions for VPL
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined( 'MOODLE_INTERNAL' ) || die();
require_once($CFG->dirroot . '/mod/vpl/vpl.class.php');
$ret = true;
$vpls = $DB->get_records( 'vpl', null, '', 'id' );
foreach ($vpls as $vplinstance) {
    $vpl = new mod_vpl( null, $vplinstance->id );
    $ret = $ret && $vpl->delete_all();
}
if (! $ret) {
    print_error( 'error deleting VPL' );
}
