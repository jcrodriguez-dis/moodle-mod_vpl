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
 * SEB: Settings management for Safe Exam Browser integration.
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
 * Safe Exam Browser helper for VPL.
 */
class settings {
    /** Table name for SEB settings. */
    public const TABLE = 'vpl_seb';

    /** Array of form fields definition */
    public const FORM_FIELDS = [
        'enablesebsession' => [
            'label' => 'enablesebsession',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'preventsebsimultaneoussessions' => [
            'label' => 'preventsebsimultaneoussessions',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
            'hideif' => 'enablesebsession',
        ],
        'sebteacherpassword' => [
            'label' => 'sebteacherpassword',
            'type' => 'passwordunmask',
            'default' => '',
            'param' => PARAM_TEXT,
            'hideif' => ['enablesebsession', 'preventsebsimultaneoussessions'],
        ],
        'showsebdownloadlink' => [
            'label' => 'showsebdownloadlink',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'linkquitseb' => [
            'label' => 'linkquitseb',
            'type' => 'text',
            'default' => '',
            'param' => PARAM_URL,
        ],
        'userconfirmquit' => [
            'label' => 'userconfirmquit',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
            'hideif' => 'allowuserquitseb',
        ],
        'allowuserquitseb' => [
            'label' => 'allowuserquitseb',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'quitpassword' => [
            'label' => 'quitpassword',
            'type' => 'passwordunmask',
            'default' => '',
            'param' => PARAM_TEXT,
            'hideif' => 'allowuserquitseb',
        ],
        'allowreloadinexam' => [
            'label' => 'allowreloadinexam',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'showsebtaskbar' => [
            'label' => 'showsebtaskbar',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'showreloadbutton' => [
            'label' => 'showreloadbutton',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'showtime' => [
            'label' => 'showtime',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'showkeyboardlayout' => [
            'label' => 'showkeyboardlayout',
            'type' => 'selectyesno',
            'default' => 1,
            'param' => PARAM_BOOL,
        ],
        'showwificontrol' => [
            'label' => 'showwificontrol',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
        ],
        'enableaudiocontrol' => [
            'label' => 'enableaudiocontrol',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
        ],
        'muteonstartup' => [
            'label' => 'muteonstartup',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
            'hideif' => 'enableaudiocontrol',
        ],
        'allowcapturecamera' => [
            'label' => 'allowcapturecamera',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
        ],
        'allowcapturemicrophone' => [
            'label' => 'allowcapturemicrophone',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
        ],
        'allowspellchecking' => [
            'label' => 'allowspellchecking',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
        ],
        'activateurlfiltering' => [
            'label' => 'activateurlfiltering',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
        ],
        'filterembeddedcontent' => [
            'label' => 'filterembeddedcontent',
            'type' => 'selectyesno',
            'default' => 0,
            'param' => PARAM_BOOL,
            'hideif' => 'activateurlfiltering',
        ],
        'expressionsallowed' => [
            'label' => 'expressionsallowed',
            'type' => 'textarea',
            'default' => '',
            'param' => PARAM_RAW,
            'hideif' => 'activateurlfiltering',
        ],
        'regexallowed' => [
            'label' => 'regexallowed',
            'type' => 'textarea',
            'default' => '',
            'param' => PARAM_RAW,
            'hideif' => 'activateurlfiltering',
        ],
        'expressionsblocked' => [
            'label' => 'expressionsblocked',
            'type' => 'textarea',
            'default' => '',
            'param' => PARAM_RAW,
            'hideif' => 'activateurlfiltering',
        ],
        'regexblocked' => [
            'label' => 'regexblocked',
            'type' => 'textarea',
            'default' => '',
            'param' => PARAM_RAW,
            'hideif' => 'activateurlfiltering',
        ],
    ];

    /**
     * Fields that belong to the generated config payload.
     */
    public const CONFIG_FIELDS = [
        'linkquitseb',
        'userconfirmquit',
        'allowuserquitseb',
        'quitpassword',
        'allowreloadinexam',
        'showsebtaskbar',
        'showreloadbutton',
        'showtime',
        'showkeyboardlayout',
        'showwificontrol',
        'enableaudiocontrol',
        'muteonstartup',
        'allowcapturecamera',
        'allowcapturemicrophone',
        'allowspellchecking',
        'activateurlfiltering',
        'filterembeddedcontent',
        'expressionsallowed',
        'regexallowed',
        'expressionsblocked',
        'regexblocked',
    ];

    /**
     * Associative array of Boolean fields that are stored as integers in the database.
     */
    public const BOOLEAN_FIELDS = [
        'showsebdownloadlink' => true,
        'enablesebsession' => true,
        'preventsebsimultaneoussessions' => true,
        'userconfirmquit' => true,
        'allowuserquitseb' => true,
        'allowreloadinexam' => true,
        'showsebtaskbar' => true,
        'showreloadbutton' => true,
        'showtime' => true,
        'showkeyboardlayout' => true,
        'showwificontrol' => true,
        'enableaudiocontrol' => true,
        'muteonstartup' => true,
        'allowcapturecamera' => true,
        'allowcapturemicrophone' => true,
        'allowspellchecking' => true,
        'activateurlfiltering' => true,
        'filterembeddedcontent' => true,
    ];

    /**
     * Fields stored in the SEB table as key and definition for the form as values.
     *
     * @return array
     */
    public static function get_form_fields(): array {
        return self::FORM_FIELDS;
    }

    /**
     * Return a default SEB settings object.
     *
     * @return \stdClass
     */
    public static function get_defaults(): \stdClass {
        $defaults = new \stdClass();
        foreach (self::get_form_fields() as $field => $definition) {
            $defaults->$field = $definition['default'];
        }
        return $defaults;
    }

    /**
     * Return the SEB settings record for a VPL instance.
     *
     * @param int $vplid VPL instance id.
     * @return \stdClass|null
     */
    public static function get_for_vpl(int $vplid): ?\stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['vplid' => $vplid]);
        return $record === false ? null : $record;
    }

    /**
     * Get SEB values object from VPL id.
     * Returns default values if no record is found.
     *
     * @param int $vplid Id of the VPL instance
     * @return \stdClass
     */
    public static function get_values_from_vplid($vplid): \stdClass {
        $values = self::get_defaults();
        $record = self::get_for_vpl((int)$vplid);
        if ($record) {
            foreach (self::get_form_fields() as $field => $definition) {
                if (isset($record->$field)) {
                    $values->$field = self::normalize_value($field, $record->$field);
                }
            }
        }
        if (empty($values->vplid)) {
            $values->vplid = $vplid;
        }
        return $values;
    }

    /**
     * Save the SEB settings for a VPL instance.
     *
     * @param int $vplid VPL instance id.
     * @param \stdClass $data Submitted form data.
     * @param int $usermodified User id modifying the settings.
     * @return void
     */
    public static function save_for_vpl(int $vplid, \stdClass $data, int $usermodified): void {

        global $DB;

        if ((int)($data->sebrequired ?? 0) !== 2) {
            self::delete_for_vpl($vplid);
            return;
        }

        $existing = self::get_for_vpl($vplid);
        $record = new \stdClass();
        foreach (self::get_form_fields() as $field => $definition) {
            if (property_exists($data, $field)) {
                $value = $data->$field;
            } else if ($existing && isset($existing->$field)) {
                $value = $existing->$field;
            } else {
                $value = $definition['default'];
            }
            $record->$field = self::normalize_value($field, $value);
        }
        $record->vplid = $vplid;

        if (!self::should_store($record)) {
            if (isset($data->sebsessionsdelete) && $data->sebsessionsdelete) {
                session_manager::delete_for_vpl($vplid);
            }
            return;
        }

        $record->usermodified = $usermodified;
        $record->timemodified = time();

        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated ?? $record->timemodified;
            $DB->update_record(self::TABLE, $record);
        } else {
            $record->timecreated = $record->timemodified;
            $DB->insert_record(self::TABLE, $record);
        }
        if (isset($data->sebsessionsdelete) && $data->sebsessionsdelete) {
            session_manager::delete_for_vpl($vplid);
        }
    }

    /**
     * Delete SEB settings for a VPL instance.
     *
     * @param int $vplid VPL instance id.
     * @return void
     */
    public static function delete_for_vpl(int $vplid): void {
        global $DB;
        session_manager::delete_for_vpl($vplid);
        $DB->delete_records(self::TABLE, ['vplid' => $vplid]);
    }

    /**
     * Return the filename used for the downloadable configuration.
     *
     * @return string
     */
    public static function get_download_filename(): string {
        return 'config.seb';
    }

    /**
     * Return the VPL start URL used in the downloadable configuration.
     *
     * @param \stdClass $record SEB record.
     * @return string
     */
    protected static function get_start_url(\stdClass $record): string {
        return config_payload::get_start_url($record);
    }

    /**
     * Get the course module id from a VPL instance id.
     *
     * @param int $vplid VPL instance id.
     * @return int The course module id, or 0 if not found.
     */
    public static function get_cmid(int $vplid): int {
        $cm = \get_coursemodule_from_instance('vpl', $vplid);
        return $cm ? (int)$cm->id : 0;
    }

    /**
     * Should we store a SEB record for this VPL
     *
     * @param \stdClass $record SEB settings record.
     * @return bool
     */
    protected static function should_store(\stdClass $record): bool {
        $previous = self::get_for_vpl($record->vplid) ?? self::get_defaults();
        foreach (self::get_form_fields() as $field => $definition) {
            $current = self::normalize_value($field, $record->$field ?? $definition['default']);
            $default = self::normalize_value($field, $previous->$field);
            if ($current !== $default) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalise a field value before persisting or comparing it.
     *
     * @param string $field Field name.
     * @param mixed $value Value to normalise.
     * @return mixed
     */
    public static function normalize_value(string $field, $value) {
        if ($value === null) {
            $defaults = self::get_defaults();
            $value = $defaults->$field ?? null;
        }
        if (isset(self::BOOLEAN_FIELDS[$field])) {
            return (int)!empty($value);
        }
        return trim((string)$value);
    }
}
