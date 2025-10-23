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

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/list_util.class.php');
require_once(dirname(__FILE__).'/vpl_submission.class.php');

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
            'examples',
    ];
    foreach ($filters as $sel) {
        $urlbase->param('selection', $sel);
        $url = $urlbase->out(false);
        $urls[$url] = get_string($sel, VPL);
        $urlindex[$sel] = $url;
    }
    if (! isset($urlindex[$instancefilter]) ) {
        $instancefilter = 'none';
    }
    $select = new url_select( $urls, $urlindex[$instancefilter], []);
    $select->set_label(get_string('filter'));
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
    if (! isset($urlindex[$sectionfilter]) ) {
        $sectionfilter = 'all';
    }
    $select = new url_select( $urls, $urlindex[$sectionfilter], []);
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
    $urlbase->param( 'detailedmore', '0' );
    $urlno = $urlbase->out( false );
    $urls[$urlno] = s(get_string('no'));
    $urlbase->param( 'detailedmore', '1' );
    $urlyes = $urlbase->out( false );
    $urls[$urlyes] = s(get_string('yes'));
    $select = new url_select( $urls, $value == '0' ? $urlno : $urlyes, []);
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
        if ( $cm->modname == 'vpl' ) {
            $section = $cm->sectionnum;
            $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);
            if ( method_exists($sectioninfo, 'get_component_instance') ) {
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
        if ( isset($cmsinsubsections[$cm->id]) ) {
            continue;
        }
        if ($cm->modname == 'vpl') {
            $activities[] = $cm;
        }
        if ( $cm->modname == 'subsection' ) {
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
 * Returns array of course modules of VPL activities selected by section or subsection.
 * @param stdClass $course Course object.
 * @return array Array of VPL activities.
 */
function get_vpl_activities_in_section($course, $cms, $sectionnum) {
    $modinfo = get_fast_modinfo($course);
    $activities = [];
    foreach ($cms as $cm) {
        $section = $cm->sectionnum;
        if ($section == $sectionnum) {
            $activities[] = $cm;
        } else {
            $name = get_section_name($course->id, $section);
            $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);
            if ( method_exists($sectioninfo, 'get_component_instance') ) {
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
        if ( $activity->modname == 'vpl' ) {
            $section = $activity->sectionnum;
            if ( isset( $sectionnames[$section] ) ) {
                continue;
            }
            $name = get_section_name($course->id, $section);
            $sectioninfo = $modinfo->get_section_info($section, MUST_EXIST);
            if ( method_exists($sectioninfo, 'get_component_instance') ) {
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

$id = required_param( 'id', PARAM_INT ); // Course id.

$sort = vpl_get_set_session_var('sort', '');
$sortdir = vpl_get_set_session_var('sortdir', 'down');
$instancefilter = vpl_get_set_session_var('selection', 'none');
$sectionfilter = vpl_get_set_session_var('section', 'all');
$detailedmore = vpl_get_set_session_var('detailedmore', '0');

// Check course existence.
if (! $course = $DB->get_record( "course", [ 'id' => $id ] )) {
    throw new moodle_exception('invalidcourseid');
}
require_course_login( $course );
// Load strings.
$burl = vpl_rel_url( basename( __FILE__ ), 'id', $id );
$strname = get_string( 'name' ) . ' ';
$strname .= vpl_list_util::vpl_list_arrow( $burl, 'name', $instancefilter, $sort, $sortdir );
$strvpls = get_string( 'modulenameplural', VPL );
$strsection = get_string( 'section' ) . ' ';
$strstartdate = get_string( 'startdate', VPL ) . ' ';
$strstartdate .= vpl_list_util::vpl_list_arrow( $burl, 'startdate', $instancefilter, $sort, $sortdir );
$strduedate = get_string( 'duedate', VPL ) . ' ';
$strduedate .= vpl_list_util::vpl_list_arrow( $burl, 'duedate', $instancefilter, $sort, $sortdir );

$PAGE->set_url( '/mod/vpl/index.php', [ 'id' => $id ] );
$PAGE->navbar->add( $strvpls );
$PAGE->requires->css( new moodle_url( '/mod/vpl/css/index.css' ) );
$PAGE->set_title( $strvpls );
$PAGE->set_heading( $course->fullname );
$PAGE->set_pagelayout('incourse');
echo $OUTPUT->header();
echo $OUTPUT->heading( $strvpls );

$einfo = ['context' => \context_course::instance( $course->id )];
$event = \mod_vpl\event\course_module_instance_list_viewed::create( $einfo );
$event->trigger();

$urlparms = [
    'id' => $id,
    'sort' => $sort,
    'sortdir' => $sortdir,
    'section' => $sectionfilter,
    'detailedmore' => $detailedmore,
    'selection' => $instancefilter,
];

$urlbase = new moodle_url( '/mod/vpl/index.php', $urlparms);
$vplactivities = get_vpl_activities( $course );
$sectionnamesbysectionnum = get_sections_names_by_sectionnum($course, $vplactivities);
echo $OUTPUT->render( get_select_section_filter($urlbase, $sectionnamesbysectionnum, $sectionfilter) );
$urlbase->params($urlparms);
echo $OUTPUT->render( get_select_instance_filter($urlbase, $instancefilter) );
$urlbase->params($urlparms);
echo $OUTPUT->render( get_select_detailedmore($urlbase, $detailedmore) );

if ( $sectionfilter != 'all' ) {
    // Get vpls in section or subsection.
    $vplactivities = get_vpl_activities_in_section($course, $vplactivities, $sectionfilter);
}
$timenow = time();
$vpls = [];
$cms = [];
// Get and select vpls to show.
foreach ($vplactivities as $cm) {
    $vpl = new mod_vpl($cm->id );
    $instance = $vpl->get_instance();
    if ($vpl->is_visible()) {
        $add = false;
        switch ($instancefilter) {
            case 'none' :
                $add = true;
                break;
            case 'open' :
                $min = $instance->startdate;
                $max = $instance->duedate == 0 ? PHP_INT_MAX : $instance->duedate;
                if ($timenow >= $min && $timenow <= $max) {
                    $add = true;
                }
                break;
            case 'closed' :
                $min = $instance->startdate;
                $max = $instance->duedate == 0 ? PHP_INT_MAX : $instance->duedate;
                if ($timenow < $min || $timenow > $max) {
                    $add = true;
                }
                break;
            case 'timelimited' :
                if ($instance->duedate > 0) {
                    $add = true;
                }
                break;
            case 'timeunlimited' :
                if ($instance->duedate == 0) {
                    $add = true;
                }
                break;
            case 'automaticgrading' :
                if ($instance->grade != 0 && $instance->automaticgrading > 0) {
                    $add = true;
                }
                break;
            case 'manualgrading' :
                if ($vpl->get_grade() != 0 && $instance->automaticgrading == 0) {
                    $add = true;
                }
                break;
            case 'examples' :
                if ($instance->example) {
                    $add = true;
                }
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
    if ($vpl->has_capability(VPL_GRADE_CAPABILITY) ||
        $vpl->has_capability(VPL_MANAGE_CAPABILITY)) {
        $grader = true;
    } else if ($vpl->has_capability( VPL_SUBMIT_CAPABILITY )) {
        $student = true;
    }
    $instance = $vpl->get_instance();
    if ($vpl->get_grade() != 0 && ! $instance->example) {
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
if ($sort > '') {
    $corder = new vpl_list_util();
    $corder->set_order( $sort, $sortdir == 'down' );
    usort($vpls, [$corder, 'cpm']);
}

// Generate table.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [
        '#',
        $strsection,
        $strname,
];
$table->align = [
        'left',
        'left',
        'left',
];
if ($startdate) {
    $table->head[] = $strstartdate;
    $table->align[] = 'center';
}
if ($duedate) {
    $table->head[] = $strduedate;
    $table->align[] = 'center';
}
if ($grader && ! $nograde) {
    $table->head[] = get_string( 'submissions', VPL );
    $table->head[] = get_string( 'graded', VPL );
    $table->align[] = 'right';
    $table->align[] = 'right';
}
if ($student && ! $nograde) {
    $table->head[] = get_string(vpl_get_gradenoun_str());
    $table->align[] = 'left';
}
if ($detailedmore) {
    $table->head[] = get_string( 'detailedmore' );
    $table->align[] = 'left';
}

$baseurlsection = vpl_abs_href( '/course/view.php', 'id', $course->id );
$table->data = [];
$totalsubs = 0;
$totalgraded = 0;
foreach ($vpls as $vpl) {
    $instance = $vpl->get_instance();
    $cmid = $vpl->get_course_module()->id;
    $url = vpl_rel_url( 'view.php', 'id', $cmid );
    $sectionnum = $cms[$cmid]->sectionnum;
    $sectionname = $sectionnamesbysectionnum[$sectionnum];
    $row = [
            count( $table->data ) + 1,
            "<a href='$baseurlsection#section-$sectionnum'>{$sectionname}</a>",
            "<a href='$url'>{$vpl->get_printable_name()}</a>",
    ];
    if ($startdate) {
        $row[] = $instance->startdate > 0 ? userdate( $instance->startdate ) : '';
    }
    if ($duedate) {
        $row[] = $instance->duedate > 0 ? userdate( $instance->duedate ) : '';
    }
    if ($grader) {
        if ($vpl->has_capability( VPL_GRADE_CAPABILITY )
            && $vpl->get_grade() != 0 && ! $instance->example) {
            $info = vpl_list_util::count_graded( $vpl );
            $totalsubs += $info['submissions'];
            $totalgraded += $info['graded'];
            $url = vpl_rel_url( 'views/submissionslist.php', 'id', $vpl->get_course_module()->id, 'selection', 'allsubmissions' );
            $row[] = '<a href="' . $url . '">' . $info['submissions'] . '</a>';
            // Need mark?
            if ($info['submissions'] > $info['graded'] && $vpl->get_grade() != 0
                && ! ($instance->duedate != 0 && $instance->duedate > time())) {
                $url = vpl_rel_url( 'views/submissionslist.php', 'id', $vpl->get_course_module()->id, 'selection', 'notgraded' );
                $diff = $info['submissions'] - $info['graded'];
                $row[] = '<div class="vpl_nm">' . $info['graded'] . ' <a href="' . $url . '">(' . $diff . ')</a><div>';
            } else {
                // No grade able.
                if ($vpl->get_grade() == 0 && $info['graded'] == 0) {
                    $row[] = '-';
                } else {
                    $row[] = $info['graded'];
                }
            }
        } else {
            $row[] = '';
            $row[] = '';
        }
    }
    if ($student) {
        if (! $vpl->has_capability( VPL_GRADE_CAPABILITY )
            && $vpl->has_capability( VPL_SUBMIT_CAPABILITY )
            && $vpl->get_grade() != 0 && ! $instance->example) {
            $subinstance = $vpl->last_user_submission( $USER->id );
            if ($subinstance) { // Submitted.
                $submission = new mod_vpl_submission( $vpl, $subinstance );
                if ($subinstance->dategraded > 0 && $vpl->get_visiblegrade()) {
                    $text = $submission->get_grade_core();
                } else {
                    $result = $submission->getCE();
                    $text = '';
                    if ($result['executed'] !== 0) {
                        $prograde = $submission->proposedGrade( $result['execution'] );
                        if ($prograde > '') {
                            $text = get_string( 'proposedgrade', VPL, $submission->get_grade_core( $prograde ) );
                        }
                    } else {
                        $text = get_string( 'nograde' );
                    }
                }
            } else { // No submitted.
                $text = get_string( 'nosubmission', VPL );
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
    $table->data[] = $row;
}
if ($totalsubs > 0) {
    $row = ['', '', ''];
    if ($startdate) {
        $row[] = '';
    }
    if ($duedate) {
        $row[] = '';
    }
    end( $row );
    $row[key( $row )] = get_string( 'total' );
    $row[] = $totalsubs;
    $row[] = $totalgraded;
    if ($detailedmore) {
        $row[] = '';
    }
    $table->data[] = $row;
}
echo "<br>";
echo html_writer::table( $table );

if (is_siteadmin() || has_capability(VPL_MANAGE_CAPABILITY, $einfo['context'])) {
    $url = new moodle_url( '/mod/vpl/views/checkvpls.php', ['id' => $id] );
    echo html_writer::link($url, get_string( 'checkgroups', VPL ), ['class' => 'btn btn-secondary']);
}

echo $OUTPUT->footer();
