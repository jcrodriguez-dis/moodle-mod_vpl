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
 * Class for logging of execution options update events
 *
 * @package mod_vpl
 * @copyright 2016 onwards Frantisek Galcik
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Frantisek Galcik <frantisek.galcik@upjs.sk>
 */
namespace mod_vpl\event;

require_once(dirname( __FILE__ ) . '/../../locallib.php');
defined( 'MOODLE_INTERNAL' ) || die();
class vpl_outputlist_viewed extends vpl_base {
    protected function init() {
        parent::init();
        $this->data ['crud'] = 'r';
        $this->legacyaction = 'view outputlist';
    }
    public function get_description() {
        return $this->get_description_mod('view outputlist');
    }
}
