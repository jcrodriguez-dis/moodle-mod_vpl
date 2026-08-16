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
 * List all VPL instances in a course
 *
 * @package mod_vpl
 * @copyright 2009 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodriguez-del-Pino
 **/

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/list_util.class.php');
require_once(__DIR__ . '/vpl_submission.class.php');
require_once($CFG->libdir . '/tablelib.php');

use mod_vpl\util\activity_modes;

/**
 * Returns a select for instance filter.
 *
 * @param moodle_url $urlbase Base URL to use.
 * @param string $instancefilter Instance filter value.
 * @return url_select
 */
function get_select_instance_filter($urlbase, $instancefilter) {
    $urls = [];
    $urlindex = [];
    $urlbase->param('selection', 'none');
    $noneurl = $urlbase->out(false);
    $urls[$noneurl] = get_string('none');
    $urlindex['none'] = $noneurl;
    $filters = [
            'open',
            'closed',
            'timelimited',
            'timeunlimited',
            'automaticgrading',
            'manualgrading',
    ];
    foreach ($filters as $sel) {
        $urlbase->param('selection', $sel);
        $url = $urlbase->out(false);
        $urls[$url] = get_string($sel, VPL);
        $urlindex[$sel] = $url;
    }
    if (! isset($urlindex[$instancefilter])) {
        $instancefilter = 'none';
    }
    $select = new url_select($urls, $urlindex[$instancefilter], []);
    $select->set_label(get_string('filter'));
    return $select;
}

/**
 * Returns a select for activity mode filter.
 *
 * @param moodle_url $urlbase Base URL to use.
 * @param string $activitymodefilter Current activity mode filter value.
 * @return url_select
 */
function get_select_activitymode_filter($urlbase, $activitymodefilter) {
    $urls = [];
    $urlindex = [];
    $urlbase->param('activitymode', 'all');
    $allurl = $urlbase->out(false);
    $urls[$allurl] = get_string('all');
    $urlindex['all'] = $allurl;
    $modes = [
        activity_modes::NORMAL,
        activity_modes::NOSTUDENTS,
        activity_modes::STUDENTSREADONLY,
        activity_modes::BASEDON,
        activity_modes::VPLQUESTION,
        activity_modes::EXAMPLE,
    ];
    foreach ($modes as $mode) {
        $urlbase->param('activitymode', $mode);
        $url = $urlbase->out(false);
        $urls[$url] = get_string(activity_modes::get_i18n_key($mode), VPL);
        $urlindex[$mode] = $url;
    }
    $current = isset($urlindex[$activitymodefilter]) ? $urlindex[$activitymodefilter] : $urlindex['all'];
    $select = new url_select($urls, $current, []);
    $select->set_label(get_string('activity_mode', VPL));
    return $select;
}

/**
 * Returns a select for section filter.
 *
 * @param moodle_url $urlbase Base URL to use.
 * @param array $sectionnames Array of section names indexed by section number.
 * @param string $sectionfilter Section filter value.
 * @return url_select
 */
function get_select_section_filter($urlbase, $sectionnames, $sectionfilter) {
    $urls = [];
    $urlindex = [];
    $urlbase->param('section', 'all');
    $allurl = $urlbase->out(false);
    $urls[$allurl] = get_string('all');
    $urlindex['all'] = $allurl;
    foreach ($sectionnames as $section => $sectionname) {
        $urlbase->param('section', $section);
        $url = $urlbase->out(false);
        $urls[$url] = $sectionname;
        $urlindex[$section] = $url;
    }
    if (! isset($urlindex[$sectionfilter])) {
        $sectionfilter = 'all';
    }
    $select = new url_select($urls, $urlindex[$sectionfilter], []);
    $select->set_label(get_string('section'));
    return $select;
}

/**
 * Returns a select for detailed more.
 *
 * @param moodle_url $urlbase Base URL to use.
 * @param string $value Value to select.
 * @return url_select
 */
function get_select_detailedmore($urlbase, $value = '0') {
    $urls = [];
    $urlbase->param('detailedmore', '0');
    $urlno = $urlbase->out(false);
    $urls[$urlno] = s(get_string('no'));
    $urlbase->param('detailedmore', '1');
    $urlyes = $urlbase->out(false);
    $urls[$urlyes] = s(get_string('yes'));
    $select = new url_select($urls, $value == '0' ? $urlno : $urlyes, []);
    $select->set_label(get_string('detailedmore'));
    return $select;
}

/**
 * Returns array of course modules of VPL activities in the order they appears in the course.
 * @param stdClass $course Course object.
 * @return array Array of VPL activities.
 */
function get_vpl_activities($course) {
    $modinfo = get_fast_modinfo($course);
    $cms = $modinfo->get_cms();
    $cmssubsections = [];
    $cmsinsubsections = [];
    // Process each course module searching for VPL activity in a subsection.
    foreach ($cms as $cm) {
        if ($cm->modname == 'vpl') {
            $section = $cm->sectionnum;
            $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);
            if (method_exists($sectioninfo, 'get_component_instance')) {
                // Check if is a subsection.
                $delegate = $sectioninfo->get_component_instance();
                if ($delegate) {
                    // Add activity to subsection list.
                    if (!isset($cmssubsections[$section])) {
                        $cmssubsections[$section] = [];
                    }
                    $cmssubsections[$section][] = $cm;
                    $cmsinsubsections[$cm->id] = true;
                }
            }
        }
    }
    $activities = [];
    foreach ($cms as $cm) {
        // Skip activities in subsections.
        if (isset($cmsinsubsections[$cm->id])) {
            continue;
        }
        if ($cm->modname == 'vpl') {
            $activities[] = $cm;
        }
        if ($cm->modname == 'subsection') {
            $delegate = $cm->get_delegated_section_info();
            if ($delegate) {
                $section = $delegate->sectionnum;
                if (isset($cmssubsections[$section])) {
                    // Add VPL activity of current subsection.
                    foreach ($cmssubsections[$section] as $subcm) {
                        $activities[] = $subcm;
                    }
                }
            }
        }
    }
    return $activities;
}

/**
 * Returns VPL activities course modules selected by section number that includes subsections.
 * @param stdClass $course Course object.
 * @param array $cms Array of course modules of VPL activities.
 * @param int $sectionnum Section number.
 * @return array $cms Array of course modules of VPL activities.
 */
function get_vpl_activities_in_section($course, $cms, $sectionnum) {
    $modinfo = get_fast_modinfo($course);
    $activities = [];
    foreach ($cms as $cm) {
        $section = $cm->sectionnum;
        if ($section == $sectionnum) {
            $activities[] = $cm;
        } else {
            $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);
            if (method_exists($sectioninfo, 'get_component_instance')) {
                // Check if is a subsection and get parent.
                $delegate = $sectioninfo->get_component_instance();
                if ($delegate) {
                    $parent = $delegate->get_parent_section();
                    if ($parent->section == $sectionnum) {
                        $activities[] = $cm;
                    }
                }
            }
        }
    }
    return $activities;
}

/**
 * Returns array of sections names indexed by section number.
 * @param stdClass $course Course object.
 * @param array $vplactivities Array of course module of VPL activities.
 * @return array Array of section names indexed by section number.
 */
function get_sections_names_by_sectionnum($course, $vplactivities) {
    $modinfo = get_fast_modinfo($course);
    $sectionnames = [];
    foreach ($vplactivities as $activity) {
        if ($activity->modname == 'vpl') {
            $section = $activity->sectionnum;
            if (isset($sectionnames[$section])) {
                continue;
            }
            $name = get_section_name($course->id, $section);
            $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);
            if (method_exists($sectioninfo, 'get_component_instance')) {
                // Check if is a subsection and get parent.
                $delegate = $sectioninfo->get_component_instance();
                if ($delegate) {
                    $parent = $delegate->get_parent_section();
                    $name = get_section_name($course->id, $parent->section) . ' / ' . $name;
                }
            }
            $sectionnames[$section] = $name;
        }
    }
    return $sectionnames;
}

global $USER, $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT); // Course id.

$sort = vpl_get_set_session_var('sort', '');
$sortdir = vpl_get_set_session_var('sortdir', SORT_DESC);
$instancefilter = vpl_get_set_session_var('selection', 'none');
$sectionfilter = vpl_get_set_session_var('section', 'all');
$activitymodefilter = vpl_get_set_session_var('activitymode', 'all');
$detailedmore = vpl_get_set_session_var('detailedmore', '0');
$download = optional_param('download', '', PARAM_ALPHA);
$downloading = $download != '';

// Check course existence.
if (! $course = $DB->get_record("course", [ 'id' => $id ])) {
    throw new moodle_exception('invalidcourseid');
}
require_course_login($course);
// Load strings.
$strname = get_string('name');
$strvpls = get_string('modulenameplural', VPL);
$strsection = get_string('section');
$strstartdate = get_string('startdate', VPL);
$strduedate = get_string('duedate', VPL);

if (! $downloading) {
    $PAGE->set_url('/mod/vpl/index.php', [ 'id' => $id ]);
    $PAGE->navbar->add($strvpls);
    $PAGE->set_title($strvpls);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('incourse');
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strvpls);
} else {
    @ini_set('display_errors', '0');
    $CFG->debugdisplay = 0;
}

$einfo = ['context' => \context_course::instance($course->id)];
$event = \mod_vpl\event\course_module_instance_list_viewed::create($einfo);
$event->trigger();

$urlparms = [
    'id' => $id,
    'sort' => $sort,
    'sortdir' => $sortdir,
    'section' => $sectionfilter,
    'activitymode' => $activitymodefilter,
    'detailedmore' => $detailedmore,
    'selection' => $instancefilter,
];

$urlbase = new moodle_url('/mod/vpl/index.php', $urlparms);
$vplactivities = get_vpl_activities($course);
$sectionnamesbysectionnum = get_sections_names_by_sectionnum($course, $vplactivities);
if (! $downloading) {
    echo $OUTPUT->render(get_select_section_filter($urlbase, $sectionnamesbysectionnum, $sectionfilter));
    $urlbase->params($urlparms);
    echo $OUTPUT->render(get_select_activitymode_filter($urlbase, $activitymodefilter));
    $urlbase->params($urlparms);
    echo $OUTPUT->render(get_select_instance_filter($urlbase, $instancefilter));
    $urlbase->params($urlparms);
    echo $OUTPUT->render(get_select_detailedmore($urlbase, $detailedmore));
}

if ($sectionfilter != 'all') {
    // Get vpls in section or subsection.
    $vplactivities = get_vpl_activities_in_section($course, $vplactivities, $sectionfilter);
}
$timenow = time();
$vpls = [];
$cms = [];
// Get and select vpls to show.
foreach ($vplactivities as $cm) {
    $vpl = new mod_vpl($cm->id);
    $instance = $vpl->get_instance();
    if ($vpl->is_visible()) {
        $add = false;
        switch ($instancefilter) {
            case 'none':
                $add = true;
                break;
            case 'open':
                $min = $instance->startdate;
                $max = $instance->duedate == 0 ? PHP_INT_MAX : $instance->duedate;
                if ($timenow >= $min && $timenow <= $max) {
                    $add = true;
                }
                break;
            case 'closed':
                $min = $instance->startdate;
                $max = $instance->duedate == 0 ? PHP_INT_MAX : $instance->duedate;
                if ($timenow < $min || $timenow > $max) {
                    $add = true;
                }
                break;
            case 'timelimited':
                if ($instance->duedate > 0) {
                    $add = true;
                }
                break;
            case 'timeunlimited':
                if ($instance->duedate == 0) {
                    $add = true;
                }
                break;
            case 'automaticgrading':
                if ($instance->grade != 0 && $instance->automaticgrading > 0) {
                    $add = true;
                }
                break;
            case 'manualgrading':
                if ($vpl->get_grade() != 0 && $instance->automaticgrading == 0) {
                    $add = true;
                }
                break;
        }
        if ($add && $activitymodefilter !== 'all') {
            $add = $vpl->is_mode((int)$activitymodefilter);
        }
        if ($add) {
            $vpls[] = $vpl;
            $cms[$cm->id] = $cm;
        }
    }
}
// Is the user a grader?
$grader = false;
$student = false;
$startdate = false;
$duedate = false;
$nograde = true;
foreach ($vpls as $vpl) {
    if ($vpl->is_teacher()) {
        $grader = true;
    } else if ($vpl->has_capability(VPL_SUBMIT_CAPABILITY)) {
        $student = true;
    }
    $instance = $vpl->get_instance();
    if ($vpl->get_grade() != 0) {
        $nograde = false;
    }
    if ($instance->startdate > 0) {
        $startdate = true;
    }
    if ($instance->duedate > 0) {
        $duedate = true;
    }
}
// If no instance with grade.
$grader = $grader && ! $nograde;
$student = $student && ! $nograde;

// The usort of old PHP versions don't call static class functions.
if ($sort != '') {
    $corder = new vpl_list_util();
    $corder->set_order($sort, $sortdir != SORT_DESC);
    usort($vpls, [$corder, 'cpm']);
}

// Build columns and headers.
$fields = ['num', 'section', 'name'];
$headers = ['#', $strsection, $strname];
if ($startdate) {
    $fields[] = 'startdate';
    $headers[] = $strstartdate;
}
if ($duedate) {
    $fields[] = 'duedate';
    $headers[] = $strduedate;
}
if ($grader) {
    $fields[] = 'submissions';
    $fields[] = 'graded';
    $fields[] = 'actions';
    $headers[] = get_string('submissions', VPL);
    $headers[] = get_string('graded', VPL);
    $headers[] = get_string('actions');
}
if ($student) {
    $fields[] = 'grade';
    $headers[] = get_string(vpl_get_gradenoun_str());
}
if ($detailedmore) {
    $fields[] = 'detailedmore';
    $headers[] = get_string('detailedmore');
}

$baseurlsection = vpl_abs_href('/course/view.php', 'id', $course->id);
$tabledata = [];
$totalsubs = 0;
$totalgraded = 0;
$rownum = 0;
foreach ($vpls as $vpl) {
    $instance = $vpl->get_instance();
    $cmid = $vpl->get_course_module()->id;
    $url = vpl_rel_url('view.php', 'id', $cmid);
    $sectionnum = $cms[$cmid]->sectionnum;
    $sectionname = s($sectionnamesbysectionnum[$sectionnum]);
    $rownum++;
    $row = [
            $rownum,
            "<a href='$baseurlsection#section-$sectionnum'>{$sectionname}</a>",
            "<a href='$url'>{$vpl->get_printable_name()}</a>",
    ];
    if ($startdate) {
        $row[] = $instance->startdate > 0 ? userdate($instance->startdate) : '';
    }
    if ($duedate) {
        $row[] = $instance->duedate > 0 ? userdate($instance->duedate) : '';
    }
    if ($grader) {
        if ($vpl->is_teacher() && $vpl->get_grade() != 0) {
            $cmid = $vpl->get_course_module()->id;
            $status = $vpl->get_submissions_status();
            $totalsubs += $status->subcount;
            $totalgraded += $status->gradedcount;
            $row[] = $OUTPUT->action_link(
                new moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $cmid]),
                get_string('submissions_overview_short', 'mod_vpl', $status),
                null,
                ['class' => 'btn btn-secondary'],
            );
            $gradeable = $vpl->get_grade() != 0;
            $needgrade = $gradeable && ($status->gradedcount < $status->subcount);
            if ($instance->duedate != 0 && $instance->duedate > $timenow) {
                $needgrade = false;
            }
            if ($gradeable && $status->subcount > 0) {
                $row[] = $OUTPUT->action_link(
                    new moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $cmid, 'selection' => 'graded']),
                    get_string('submissions_graded_overview_short', 'mod_vpl', $status),
                    null,
                    ['class' => 'btn btn-secondary'],
                );
            } else {
                $row[] = '';
            }
            if ($needgrade) {
                $str = s(get_string('gradeverb'));
                $row[] = $OUTPUT->action_link(
                    new moodle_url('/mod/vpl/views/submissionslist.php', ['id' => $cmid, 'selection' => 'notgraded']),
                    $str,
                    null,
                    ['class' => 'btn btn-secondary'],
                );
            } else {
                $row[] = '';
            }
        } else {
            $row[] = '';
            $row[] = '';
            $row[] = '';
        }
    }
    if ($student) {
        $isvplstudent = ! $vpl->is_teacher();
        $isvplstudent = $isvplstudent && $vpl->has_capability(VPL_SUBMIT_CAPABILITY);
        $isvplstudent = $isvplstudent && $vpl->get_grade() != 0 && ! $vpl->is_example();
        if ($isvplstudent) {
            $subinstance = $vpl->last_user_submission($USER->id);
            if ($subinstance) { // Submitted.
                $submission = new mod_vpl_submission($vpl, $subinstance);
                if ($subinstance->dategraded > 0 && $vpl->get_visiblegrade()) {
                    $text = $submission->get_grade_core();
                } else {
                    $result = $submission->getCE();
                    $text = '';
                    if ($result['executed'] !== 0) {
                        $prograde = $submission->proposedGrade($result['execution']);
                        if ($prograde !== '') {
                            $text = get_string('proposedgrade', VPL, $submission->get_grade_core($prograde));
                        }
                    } else {
                        $text = get_string('notgraded', VPL);
                    }
                }
            } else { // No submitted.
                $text = get_string('nosubmission', VPL);
                if ($vpl->is_submit_able()) {
                    $text = '<div class="vpl_nm">' . $text . '</div>';
                }
            }
            $row[] = $text;
        } else {
            $row[] = '-';
        }
    }
    if ($detailedmore) {
        $row[] = $vpl->str_submission_restriction();
    }
    $tabledata[] = $row;
}
// Add totals row if grader.
if ($grader) {
    $row = [];
    $columnsbeforetotals = 2;
    if ($startdate) {
        $columnsbeforetotals++;
    }
    if ($duedate) {
        $columnsbeforetotals++;
    }
    for ($i = 0; $i < $columnsbeforetotals; $i++) {
        $row[] = '';
    }
    $row[] = get_string('total');
    $row[] = $totalsubs;
    $row[] = $totalgraded;
    $row[] = '';
    if ($student) {
        $row[] = '';
    }
    if ($detailedmore) {
        $row[] = '';
    }
    $tabledata[] = $row;
}
$table = new flexible_table('vpl-index-' . $id);
$table->is_downloading($download, $course->shortname . '_vpl_list', $course->shortname);
$table->define_baseurl(new moodle_url('/mod/vpl/index.php', $urlparms));
$table->define_columns($fields);
$table->define_headers($headers);
$table->column_style('submissions', 'text-align', 'right');
$table->column_style('graded', 'text-align', 'right');
$table->set_attribute('class', 'generaltable mod_index');
if ($downloading) {
    $table->setup();
    foreach ($tabledata as $row) {
        $table->add_data($row);
    }
    $table->finish_output();
    exit(0);
}
if ($grader) {
    $totalsrow = array_pop($tabledata);
}
$table->set_control_variables([TABLE_VAR_SORT => 'sort', TABLE_VAR_DIR => 'sortdir']);
$table->sortable(true, $sort, $sortdir);
$sortablefields = ['name', 'startdate', 'duedate'];
foreach ($fields as $field) {
    if (!in_array($field, $sortablefields)) {
        $table->no_sorting($field);
    }
}
$table->collapsible(true);
$table->show_download_buttons_at([TABLE_P_BOTTOM]);
echo '<br>';
$table->setup();
foreach ($tabledata as $row) {
    $table->add_data($row);
}
if (isset($totalsrow)) {
    $table->add_data($totalsrow, 'table-active fw-bold vpl-totals-row');
}
$table->finish_output();

if (is_siteadmin() || has_capability(VPL_MANAGE_CAPABILITY, $einfo['context'])) {
    $url = new moodle_url('/mod/vpl/views/checkvpls.php', ['id' => $id]);
    echo html_writer::link($url, get_string('checkgroups', VPL), ['class' => 'btn btn-secondary']);
}

echo $OUTPUT->footer();
