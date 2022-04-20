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
 * Class for mod_vpl home link widget
 *
 * @package    mod_vpl
 * @copyright  2022 CDO-Global
 * @author     Valentin Afanasev
 */

namespace mod_vpl\output;

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/mod/vpl/locallib.php");

use renderer_base;

class homelink implements \renderable, \templatable {
    public function export_for_template(renderer_base $output) {
        return [
            'version' => vpl_get_version(),
        ];
    }
}
