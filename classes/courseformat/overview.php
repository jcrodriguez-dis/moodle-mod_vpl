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
 * Class for VPL overview feature integration.
 *
 * @package mod_vpl
 * @copyright 2025 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */

namespace mod_vpl\courseformat;

use cm_info;
use core_courseformat\local\overview\overviewitem;
use core\output\action_link;
use core\output\local\properties\text_align;
use core\output\local\properties\button;
use core\url;
use mod_vpl\dates;

/**
 * VPL overview integration.
 *
 * @copyright 2025 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 */
class overview extends \core_courseformat\activityoverviewbase {
    /** @var \mod_vpl $vpl the vpl object. */
    private \mod_vpl $vpl;

    /** @var \stdClass $submissionstatus the submission status object. */
    private \stdClass $submissionsstatus;
    /**
     * Constructor.
     *
     * @param cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
    ) {
        require_once(dirname(__FILE__) . '/../../locallib.php');
        parent::__construct($cm);
        $this->vpl = new \mod_vpl($this->cm->id);
    }

    /**
     * Get submissions status.
     *
     * @return \stdClass
     */
    public function get_submissions_status(): \stdClass {
        if (!isset($this->submissionsstatus)) {
            $this->submissionsstatus = $this->vpl->get_submissions_status();
        }
        return $this->submissionsstatus;
    }
    #[\Override]
    public function get_due_date_overview(): ?overviewitem {
        global $USER;

        $dates = new dates($this->cm, $USER->id);
        $duedate = $dates->get_due_date();
        return new overviewitem(
            name: get_string('duedate', 'mod_vpl'),
            value: $duedate,
            content: empty($duedate) ? '-' : userdate($duedate),
        );
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        if (
            $this->vpl->has_capability(VPL_GRADE_CAPABILITY) ||
                $this->vpl->has_capability(VPL_MANAGE_CAPABILITY)
        ) {
            $status = $this->get_submissions_status();
            $needgrading = $status->subcount - $status->gradedcount;
            if ($this->vpl->get_grade() != 0 && $needgrading > 0) {
                $alertlabel = get_string('numberofsubmissionsneedgrading', 'assign');
                $name = get_string('gradeverb');
                $content = new action_link(
                    url: new url('/mod/vpl/views/submissionslist.php', ['id' => $this->cm->id, 'selection' => 'notgraded']),
                    text: $name,
                    attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
                );
                return new overviewitem(
                    name: get_string('actions'),
                    value: $name,
                    content: $content,
                    textalign: text_align::CENTER,
                    alertcount: $needgrading,
                    alertlabel: $alertlabel,
                );
            }
        } else if (
            $this->vpl->has_capability(VPL_SUBMIT_CAPABILITY) &&
                $this->vpl->is_submit_able()
        ) {
            $name = get_string('submit');
            $content = new action_link(
                url: new url('/mod/vpl/forms/edit.php', ['id' => $this->cm->id]),
                text: get_string('dueeventaction', 'mod_vpl'),
                attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
            );
            return new overviewitem(
                name: get_string('actions'),
                value: $name,
                content: $content,
                textalign: text_align::CENTER,
            );
        }
        return null;
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        global $USER;
        $submissionstatusoverview = null;
        $submissionsoverview = null;
        $gradedsubmissionsoverview = null;
        if (
            $this->vpl->has_capability(VPL_GRADE_CAPABILITY) ||
                $this->vpl->has_capability(VPL_MANAGE_CAPABILITY)
        ) {
            $status = $this->get_submissions_status();
            $content = new action_link(
                url: new url('/mod/vpl/views/submissionslist.php', ['id' => $this->cm->id]),
                text: get_string('submissions_overview_short', 'mod_vpl', $status),
                attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
            );
            $submissionsoverview = new overviewitem(
                name: get_string('submissions', 'mod_vpl'),
                value: $status->ugcount,
                content: $content,
                textalign: text_align::CENTER,
            );
            if ($this->vpl->get_grade() != 0) {
                $content = new action_link(
                    url: new url('/mod/vpl/views/submissionslist.php', ['id' => $this->cm->id, 'selection' => 'graded']),
                    text: get_string('submissions_graded_overview_short', 'mod_vpl', $status),
                    attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
                );
                $gradedsubmissionsoverview = new overviewitem(
                    name: get_string('graded', 'mod_vpl'),
                    value: $status->gradedcount,
                    content: $content,
                    textalign: text_align::CENTER,
                );
            }
        } else {
            if (
                $this->vpl->has_capability(VPL_SUBMIT_CAPABILITY) &&
                    $this->vpl->is_visible()
            ) {
                $userstatus = $this->vpl->last_user_submission($USER->id);
                if (!$userstatus) {
                    $content = get_string('nosubmission', 'mod_vpl');
                    $status = 0;
                } else {
                    $status = $userstatus->datesubmitted;
                    $content = new action_link(
                        url: new url('/mod/vpl/forms/submissionview.php', ['id' => $this->cm->id, 'userid' => $USER->id]),
                        text: userdate($userstatus->datesubmitted),
                    );
                }
                $submissionstatusoverview = new overviewitem(
                    name: get_string('submission', 'mod_vpl'),
                    value: $status,
                    content: $content,
                    textalign: text_align::CENTER,
                );
            }
        }
        return [
            'submissions' => $submissionsoverview,
            'gradedsubmissions' => $gradedsubmissionsoverview,
            'submissionstatus' => $submissionstatusoverview,
        ];
    }
}
