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
 * Class for mod_vpl view page
 *
 * @package    mod_vpl
 * @copyright  2022 CDO-Global
 * @author     Valentin Afanasev
 */

namespace mod_vpl\output;

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/mod/vpl/locallib.php");
require_once("{$CFG->dirroot}/mod/vpl/vpl.class.php");

use renderer_base;
use mod_vpl;

class view implements \renderable, \templatable {
    private $vpl;
    private $userid;

    /**
     * Constructor for renderable
     *
     * @param mod_vpl $vpl
     * @param int $userid
     * @return void
     */
    public function __construct(mod_vpl $vpl, int $userid) {
        $this->vpl = $vpl;
        $this->userid = $userid;
    }

    /**
     * export_for_template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $showfr = false;
        $fr_files = '';
        $fr = $this->vpl->get_required_fgm();
        if ( $fr->is_populated() ) {
            $showfr = true;
            ob_start();
            $fr->print_files(false);
            $fr_files = ob_get_clean();
        }

        $showfe = false;
        $fe_files = '';
        $fe = $this->vpl->get_execution_fgm();
        if ( $this->vpl->has_capability( VPL_GRADE_CAPABILITY ) &&
            $fe->is_populated() ) {
            $showfe = true;
            ob_start();
            $fe->print_files(false);
            $fe_files = ob_get_clean();
        }
        if ( $showfr || $showfe ) {
            require_once("{$CFG->dirroot}/mod/vpl/views/sh_factory.class.php");
            \vpl_sh_factory::include_js();
        }

        return [
            'tabs'          => $this->get_tabs($output),
            'heading'       => $this->get_heading($output),
            'infoblock'     => $this->get_infoblock($output),
            'showfe'        => $showfe,
            'showfr'        => $showfr,
            'fe_files'      => $fe_files,
            'fr_files'      => $fr_files,
            'cmid'          => $this->vpl->get_course_module()->id,
            'ws_available'  => vpl_get_webservice_available(),
            'homelink'      => $this->get_homelink($output),
        ];
    }

    /**
     * Get heading for page
     *
     * @param renderer_base $output
     * @return string
     */
    protected function get_heading(renderer_base $output) {
        ob_start();
        $this->vpl->print_name();
        return ob_get_clean();
    }

    /**
     * Get html for infoblock
     *
     * @param renderer_base $output
     * @return string
     */
    protected function get_infoblock(renderer_base $output) {
        return $output->render(new infoblock($this->vpl, $this->userid));
    }

    /**
     * Get html for tabs
     *
     * @param renderer_base $output
     * @param string
     */
    protected function get_tabs(renderer_base $output) {
        ob_start();
        $this->vpl->print_view_tabs( basename( __FILE__ ) );
        return ob_get_clean();
    }

    /**
     * Get html for home link
     *
     * @param renderer_base $output
     * @return string
     */
    protected function get_homelink(renderer_base $output) {
        $inst = $this->vpl->get_instance();
        if ($inst->sebrequired || $inst->sebkeys > '') {
            return '';
        }
        return $output->render(new homelink());
    }
}

