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
 * Class for mod_vpl infoblock widget
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
use html_writer;

class infoblock implements \renderable, \templatable {
    private mod_vpl $vpl;
    private int $userid;

    public function __construct(mod_vpl $vpl, int $userid) {
        $this->vpl = $vpl;
        $this->userid = $userid;
    }

    public function export_for_template(renderer_base $output) {
        global $CFG;
        $requestedfiles = null;
        $filegroup = $this->vpl->get_required_fgm();
        $files = $filegroup->getfilelist();
        if (count( $files )) {
            $requestedfiles = [
                'text' => join(', ', $files),
                'href' => vpl_mod_href(
                    'views/downloadrequiredfiles.php', 'id', $this->vpl->get_course_module()->id,
                ),
            ];
        }
        $instance = $this->vpl->get_instance();
        $maxfiles = null;
        if (count($files) != $instance->maxfiles) {
            $maxfiles = $instance->maxfiles;
        }
        $maxfilesize = null;
        if ($instance->maxfilesize) {
            $maxfilesize = vpl_conv_size_to_string(
                $this->vpl->get_maxfilesize()
            );
        }
        $grader = $this->vpl->has_capability( VPL_GRADE_CAPABILITY );
        $grademax = null;
        $gradehidden = null;
        $gradelocked = null;
        $nograde = true;
        $gradetypescale = false;
        if ($grader) {
            require_once("{$CFG->libdir}/gradelib.php");
            if ($gie = $this->vpl->get_grade_info()) {
                $nograde = false;
                if ($gie->scaleid == 0) {
                    $grademax = format_float($gie->grademax, 5, true, true);
                    $gradehidden = $gie->hidden;
                    $gradelocked = $gie->locked;
                } else {
                    $gradetypescale = true;
                }
            }
        }
        $reductionbyevaluation = $this->vpl->get_effective_setting(
            'reductionbyevaluation',
            $this->userid,
        );
        $freeevaluations = null;
        if ($reductionbyevaluation) {
            $freeevaluations = $this->vpl->get_effective_setting(
                'freeevaluations',
                $this->userid,
            );
        }
        $graderextra = [];
        if ($grader) {
            if (!$this->vpl->get_course_module()->visible) {
                $graderextra['hidden'] = true;
            }
            if ($instance->basedon) {
                try {
                    $basedon = new mod_vpl( null, $instance->basedon );
                    $graderextra['basedon'] = html_writer::link(
                        vpl_mod_href('view.php', 'id', $basedon->cm->id),
                        $basedon->get_printable_name(),
                    );
                } catch (Exception $e) {
                    $graderextra['basedon'] = $e->getMessage();
                }
            }
            if ($instance->maxexememory) {
                $graderextra['maxexememory'] = vpl_conv_size_to_string($instance->maxexememory);
            }
            if ($instance->maxexefilesize) {
                $graderextra['maxexefilesize'] = vpl_conv_size_to_string($instance->maxexefilesize);
            }
        }

        return [
            'startdate' => $this->vpl->get_effective_setting('startdate', $this->userid),
            'duedate' => $this->vpl->get_effective_setting('duedate', $this->userid),
            'requestedfiles' => $requestedfiles,
            'maxfiles' => $maxfiles,
            'maxfilesize' => $maxfilesize,
            'instance' => $instance,
            'grader'   => $grader,
            'nograde'  => $nograde,
            'grademax' => $grademax,
            'gradehidden' => $gradehidden,
            'gradelocked' => $gradelocked,
            'gradetypescale' => $gradetypescale,
            'reductionbyevaluation' => $reductionbyevaluation,
            'freeevaluations' => $freeevaluations,
            'graderextra' => $graderextra,
            'variation' => $this->vpl->get_variation_html($this->userid),
            'fulldescription' => $this->vpl->get_fulldescription_with_basedon(),
            'shortdescription' => format_text($instance->shortdescription, FORMAT_PLAIN),
        ];
    }
}
