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
 * SEB: UI utility class for Safe Exam Browser integration.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @copyright 2026 8 Juan Carlos Rodríguez del Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 * @author Juan Carlos Rodríguez del Pino <jc.rodriguezdelpino@ulpgc.es>
 */

namespace mod_vpl\seb;

/**
 * VPL-SEB UI utility class.
 */
class ui {
    /**
     * Name of the session variable used to store the number of attempts for the teacher password.
     */
    const VAR_TEACHER_PASSWORD_ATTEMPT = 'vpl_sebteacherpassword_attempt';

    /**
     * Add SEB fields to VPL setting form.
     *
     * @param \MoodleQuickForm $mform The Moodle form object.
     * @param string $field The field name.
     * @param array $description The field description.
     * @param string $condition The condition for the field.
     * @param string $function The function to apply.
     */
    public static function add_fields_conditions_to_form(
        \MoodleQuickForm $mform,
        string $field,
        array $description,
        string $condition,
        string $function
    ) {
        if (isset($description[$condition])) {
            $dependentfields = $description[$condition];
            if (!is_array($dependentfields)) {
                $dependentfields = [$dependentfields];
            }
            foreach ($dependentfields as $dependentfield) {
                $mform->$function($field, $dependentfield, 'eq', 0);
            }
        }
    }
    /**
     * Add SEB fields to VPL setting form.
     *
     * @param \MoodleQuickForm $mform The Moodle form object.
     */
    public static function add_fields_to_form(\MoodleQuickForm $mform, int $vplid = 0) {
        if ($vplid > 0 && session_manager::exists_for_vpl($vplid)) {
            $warning = get_string('sebsessionwarning', VPL);
            $mform->addElement(
                'static',
                'seb_session_warning',
                '',
                \html_writer::div($warning, 'alert alert-warning')
            );
            $mform->addElement('selectyesno', 'sebsessionsdelete', get_string('sebsessionsdelete', VPL));
            $mform->setDefault('sebsessionsdelete', false);
            $mform->addHelpButton('sebsessionsdelete', 'sebsessionsdelete', VPL);
        }
        foreach (settings::get_form_fields() as $field => $definition) {
            $label = get_string($definition['label'], VPL);
            if ($definition['type'] === 'select') {
                $mform->addElement('select', $field, $label, $definition['options']);
            } else if ($definition['type'] === 'textarea') {
                $mform->addElement('textarea', $field, $label, [
                                    'cols' => 66,
                                    'rows' => 2,
                ]);
            } else {
                $mform->addElement($definition['type'], $field, $label);
            }
            $mform->setType($field, $definition['param']);
            $mform->setDefault($field, $definition['default']);
            $mform->hideIf($field, 'sebrequired', 'neq', 2);
            self::add_fields_conditions_to_form($mform, $field, $definition, 'disabledif', 'disabledIf');
            self::add_fields_conditions_to_form($mform, $field, $definition, 'hideif', 'hideIf');
        }
    }

    /**
     * Validate seb fields in VPL setting form.
     * @param array $data The submitted form data.
     * @param array $errors The array of form errors.
     */
    public static function validate_form_data(array $data, array &$errors) {
        $needteacherpassword = !empty($data['sebrequired']) && (int)$data['sebrequired'] === 2;
        $needteacherpassword = $needteacherpassword && !empty($data['enablesebsession']);
        $needteacherpassword = $needteacherpassword && !empty($data['preventsebsimultaneoussessions']);
        $needteacherpassword = $needteacherpassword && trim((string)($data['sebteacherpassword'] ?? '')) === '';
        if ($needteacherpassword) {
            $errors['sebteacherpassword'] = get_string('required');
        }
    }

    /**
     * Set SEB form data
     *
     * @param int|null $vplid VPL activity id
     * @param array $defaultvalues array of data to set
     */
    public static function set_form_data($vplid, &$defaultvalues) {
        if (empty($vplid)) {
            return;
        }
        $sebvalues = settings::get_values_from_vplid($vplid);
        foreach (array_keys(settings::get_form_fields()) as $fieldname) {
            if (isset($sebvalues->$fieldname)) {
                $defaultvalues[$fieldname] = $sebvalues->$fieldname;
            }
        }
    }

    /**
     * Get the link that lets a teacher explicitly check SEB from the activity view.
     *
     * @param \mod_vpl $vpl The VPL activity object.
     * @return string HTML link.
     */
    public static function get_seb_check_link($vpl): string {
        $url = new \moodle_url('/mod/vpl/view.php', [
            'id' => $vpl->get_course_module()->id,
            'sebcheck' => 1,
        ]);
        return \html_writer::link(
            $url,
            get_string('checkseb', VPL),
            ['class' => 'btn btn-primary m-1']
        );
    }

    /**
     * Get the SEB download/configuration buttons for students blocked outside SEB.
     *
     * @param \mod_vpl $vpl The VPL activity object
     * @return string HTML of the SEB launch buttons
     */
    public static function get_seb_launch_buttons($vpl) {
        $settings = settings::get_values_from_vplid($vpl->get_instance()->id);
        if (!empty($settings->enablesebsession)) {
            global $USER;
            $userid = (int)$USER->id;
        } else {
            $userid = 0;
        }
        $buttons = [];
        $session = session_manager::get_or_create_session($settings, $userid);
        $param = ['vplid' => $vpl->get_instance()->id, 'token' => $session->token1public];
        $configurl = new \moodle_url('/mod/vpl/views/download_seb_conf.php', $param);
        $launchurl = self::get_seb_launch_url($configurl);
        $buttons[] = \html_writer::link(
            $launchurl,
            get_string('launchsafeexambrowser', VPL),
            ['class' => 'btn btn-primary m-1']
        );
        if (!empty($settings->showsebdownloadlink)) {
            $buttons[] = \html_writer::link(
                $configurl,
                get_string('downloadsebconfig', VPL),
                ['class' => 'btn btn-secondary m-1']
            );
            $buttons[] = '<br><hr>';
            $buttons[] = \html_writer::link(
                new \moodle_url('https://safeexambrowser.org/download_en.html'),
                get_string('downloadsafeexambrowser', VPL),
                ['class' => 'btn btn-info m-1', 'target' => '_blank', 'rel' => 'noopener']
            );
        }
        return \html_writer::div(implode(' ', $buttons), 'my-3');
    }

    /**
     * Print the SEB download/configuration buttons for students blocked outside SEB.
     *
     * @param \mod_vpl $vpl The VPL activity object
     * @return void
     */
    public static function print_seb_launch_buttons($vpl) {
        echo self::get_seb_launch_buttons($vpl);
    }

    /**
     * Convert a normal config URL into a SEB protocol launch URL.
     *
     * @param \moodle_url $configurl HTTP(S) URL to the generated .seb file.
     * @return string
     */
    protected static function get_seb_launch_url(\moodle_url $configurl) {
        $url = $configurl->out(false);
        if (strpos($url, 'https://') === 0) {
            return 'sebs://' . substr($url, strlen('https://'));
        }
        if (strpos($url, 'http://') === 0) {
            return 'seb://' . substr($url, strlen('http://'));
        }
        return $url;
    }

    /**
     * Print the teacher password form used to replace an active SEB session.
     *
     * @param \mod_vpl $vpl VPL activity object
     * @return void
     */
    public static function print_seb_teacher_password_form($vpl) {
        global $SESSION;
        $passattempt = self::VAR_TEACHER_PASSWORD_ATTEMPT;
        $invalidpassword = false;
        $attempts = isset($SESSION->$passattempt) ? $SESSION->$passattempt : 0;
        $invalidpassword = $attempts > 0;
        $vpl->print_header();
        echo \html_writer::start_div('vpl-seb-access mx-auto', ['style' => 'max-width: 720px;']);
        vpl_notice(get_string('sebsimultaneoussessionblocked', VPL), 'warning');
        if ($invalidpassword) {
            vpl_notice(get_string('sebinvalidteacherpassword', VPL), 'warning');
            vpl_notice(get_string('attemptnumber', VPL, $attempts), 'warning');
        }

        $action = new \moodle_url('/mod/vpl/view.php', ['id' => $vpl->get_course_module()->id]);
        echo \html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $action->out(false),
            'class' => 'my-3 mx-auto',
            'style' => 'max-width: 420px;',
        ]);
        echo \html_writer::start_div('form-group');
        echo \html_writer::tag('label', get_string('sebteacherpassword', VPL), ['for' => 'id_sebteacherpassword']);
        echo \html_writer::empty_tag('input', [
            'type' => 'password',
            'name' => 'sebteacherpassword',
            'id' => 'id_sebteacherpassword',
            'class' => 'form-control',
            'autocomplete' => 'off',
        ]);
        echo \html_writer::end_div();
        echo \html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('continue'),
            'class' => 'btn btn-primary',
        ]);
        echo \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo \html_writer::end_tag('form');
        echo \html_writer::end_div();
        $vpl->print_footer();
        die();
    }

    /**
     * Check if current browser passes the SEB check and react accordingly.
     * - If not SEB browser or bad keys following config show links to launch,
     *   download config, download SEB.
     * - If in phase1 and multiple sessions are active and not allowed then
     *   show/check teacher password form to replace session.
     * - If in phase1, send phase2 config and exit.
     * - If in phase2 and SEB check passes, do nothing.
     *
     * @param \mod_vpl $vpl VPL activity object
     * @return void
     */
    public static function seb_check(\mod_vpl $vpl) {
        global $SESSION;
        global $FULLME;
        if ($vpl->get_instance()->sebrequired != 2) { // Manual SEB not required, do nothing.
            return;
        }
        $settings = settings::get_values_from_vplid($vpl->get_instance()->id);
        $session = access_validator::get_session_and_phase($settings);
        if ($FULLME === 'remove for debug') {
            $vpl->add_notice($session->phase . ' phase', 'info');
            $vpl->add_notice("Browser config key: " . access_validator::get_browser_config_key_from_request(), 'info');
            $vpl->add_notice("Session config key1 for current URL: " . hash('sha256', $FULLME . $session->configkey1), 'info');
            $vpl->add_notice("Session config key2 for current URL: " . hash('sha256', $FULLME . $session->configkey2), 'info');
            $vpl->add_notice("Sesskey: " . sesskey(), 'info');
            $vpl->add_notice("SEB session sesskey: " . $session->sesskey, 'info');
            $vpl->add_notice("Current URL: " . $FULLME, 'info');
        }
        switch ($session->phase) {
            case -3:
            case -2:
                $str = get_string('sebsessionmismatch', COMPVPL);
                if (constant('AJAX_SCRIPT')) {
                    throw new \moodle_exception($str);
                }
                $vpl->add_notice($str, 'error');
                $vpl->print_header();
                $vpl->print_footer();
                die();
                break;
            case 0:
                // Not SEB show launch/download buttons and exit.
                $str = get_string("sebrequired", COMPVPL);
                if ($vpl->is_seb_browser()) {
                    $str .= '<br>' . get_string('sebkeys_bad', COMPVPL);
                } else {
                    $str .= '<br>' . get_string('sebrequired_bad', COMPVPL);
                }
                if (constant('AJAX_SCRIPT')) {
                    throw new \moodle_exception($str);
                }
                $vpl->add_notice($str, 'warning');
                $vpl->print_header();
                self::print_seb_launch_buttons($vpl);
                $vpl->print_footer();
                die();
                break;
            case -1:
                if (!self::is_correct_teacher_password($vpl, $settings)) {
                    self::print_seb_teacher_password_form($vpl);
                    die();
                } else {
                    session_manager::prepare_phase2($settings, $session);
                    $passattempt = self::VAR_TEACHER_PASSWORD_ATTEMPT;
                    unset($SESSION->$passattempt);
                }
                // Break intentionally omitted to allow fall-through to case 1.
            case 1:
                // In phase1.
                if (! session_manager::exists_phase2($session)) {
                    session_manager::prepare_phase2($settings, $session);
                }
                if (session_manager::is_unallowed_moodle_session($settings, $session)) {
                    if (constant('AJAX_SCRIPT')) {
                        $str = get_string('sebsimultaneoussessionblocked', VPL);
                        throw new \moodle_exception($str);
                    }
                }
                $config = session_manager::get_phase2_config_xml($settings, $session);
                session_manager::send_seb_config($config);
                break;
            case 2:
                // In phase2, SEB check passed, do nothing.
                break;
        }
    }


    /**
     * Check if the submitted teacher password is correct.
     *
     * @param \mod_vpl $vpl VPL activity object
     * @param \stdClass $settings SEB settings.
     * @return bool
     */
    public static function is_correct_teacher_password($vpl, $settings) {
        global $USER;
        global $SESSION;
        $activityid = $vpl->get_instance()->id;
        $passattempt = self::VAR_TEACHER_PASSWORD_ATTEMPT;
        $password = optional_param('sebteacherpassword', '', PARAM_TEXT);
        $sesskey = optional_param('sesskey', '', PARAM_RAW);
        if ($password === '' || $sesskey == '' || !confirm_sesskey($sesskey)) {
            return false;
        }
        if (session_manager::validate_teacher_password($settings, $password)) {
            return true;
        } else {
            if (isset($SESSION->$passattempt)) {
                $SESSION->$passattempt++;
            } else {
                $SESSION->$passattempt = 1;
            }
            $cmid = $vpl->get_course_module()->id;
            \mod_vpl\event\seb_access_denied::log([
                'objectid' => $activityid,
                'context' => \context_module::instance($cmid),
                'userid' => $USER->id,
                'other' => [
                    'reason' => 'invalid_teacher_password',
                    'activityid' => $activityid,
                    'attempts' => $SESSION->$passattempt,
                ],
            ]);
            sleep(min($SESSION->$passattempt, 6)); // Delay to mitigate brute-force attempts.
            return false;
        }
    }
}
