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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */

namespace mod_vpl\seb;

use const PARAM_BOOL;
use const PARAM_INT;
use const PARAM_RAW;
use const PARAM_TEXT;
use const PARAM_URL;

defined('MOODLE_INTERNAL') || die();

/**
 * Safe Exam Browser helper for VPL.
 */
class settings {

    /** Table name for SEB settings. */
    public const TABLE = 'vpl_seb';

    /**
     * Fields stored in the SEB table.
     *
     * @return array
     */
    public static function get_form_fields(): array {
        return [
            'requiresafeexambrowser' => [
                'label' => 'requiresafeexambrowser',
                'type' => 'select',
                'default' => 0,
                'param' => PARAM_INT,
                'options' => [
                    0 => \get_string('no'),
                    1 => \get_string('seb_configuremanually', \VPL),
                ],
            ],
            'showsebdownloadlink' => [
                'label' => 'showsebdownloadlink',
                'type' => 'selectyesno',
                'default' => 1,
                'param' => PARAM_BOOL,
            ],
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
            ],
            'sebteacherpassword' => [
                'label' => 'sebteacherpassword',
                'type' => 'passwordunmask',
                'default' => '',
                'param' => PARAM_TEXT,
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
            ],
            'adminpassword' => [
                'label' => 'adminpassword',
                'type' => 'passwordunmask',
                'default' => '',
                'param' => PARAM_TEXT,
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
            ],
            'expressionsallowed' => [
                'label' => 'expressionsallowed',
                'type' => 'textarea',
                'default' => '',
                'param' => PARAM_RAW,
            ],
            'regexallowed' => [
                'label' => 'regexallowed',
                'type' => 'textarea',
                'default' => '',
                'param' => PARAM_RAW,
            ],
            'expressionsblocked' => [
                'label' => 'expressionsblocked',
                'type' => 'textarea',
                'default' => '',
                'param' => PARAM_RAW,
            ],
            'regexblocked' => [
                'label' => 'regexblocked',
                'type' => 'textarea',
                'default' => '',
                'param' => PARAM_RAW,
            ],
            'allowedbrowserexamkeys' => [
                'label' => 'sebkeys',
                'type' => 'textarea',
                'default' => '',
                'param' => PARAM_RAW,
            ],
        ];
    }

    /**
     * Fields that belong to the generated config payload.
     *
     * @return string[]
     */
    public static function get_config_fields(): array {
        return [ 'linkquitseb', 'userconfirmquit', 'allowuserquitseb', 'quitpassword', 'adminpassword', 'allowreloadinexam', 'showsebtaskbar', 'showreloadbutton', 'showtime', 'showkeyboardlayout', 'showwificontrol', 'enableaudiocontrol', 'muteonstartup', 'allowcapturecamera', 'allowcapturemicrophone', 'allowspellchecking', 'activateurlfiltering', 'filterembeddedcontent', 'expressionsallowed', 'regexallowed', 'expressionsblocked', 'regexblocked', ];
    }

    /**
     * VPL fields that map into the new SEB table.
     *
     * @return array
     */
    public static function get_legacy_field_map(): array {
        return [ 'requiresafeexambrowser' => 'sebrequired', 'allowedbrowserexamkeys' => 'sebkeys'];
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
     * Build a form values object from VPL instance record.
     *
     * @param \stdClass $instance VPL instance record.
     * @return \stdClass
     */
    public static function get_form_values_from_instance(\stdClass $instance): \stdClass {
        
        $values = self::get_defaults();

        foreach (self::get_form_fields() as $field => $definition) {
            if (isset($instance->$field)) {
                $values->$field = self::normalize_value($field, $instance->$field);
            }
        }

        foreach (self::get_legacy_field_map() as $field => $legacyfield) {
            if ($values->$field === self::get_defaults()->$field && isset($instance->$legacyfield)) {
                $values->$field = self::normalize_value($field, $instance->$legacyfield);
            }
        }

        if (!empty($instance->id)) {
            
            $record = self::get_for_vpl((int)$instance->id);
            
            if ($record) {
                foreach (self::get_form_fields() as $field => $definition) {
                    if (isset($record->$field)) {
                        $values->$field = self::normalize_value($field, $record->$field);
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Extract a record from a submitted form object.
     *
     * @param \stdClass $data Submitted form data.
     * @return \stdClass
     */
    public static function extract_from_form(\stdClass $data): \stdClass {
        
        $record = new \stdClass();

        foreach (self::get_form_fields() as $field => $definition) {
            
            $value = $data->$field ?? $definition['default'];
            
            foreach (self::get_legacy_field_map() as $newfield => $legacyfield) {
                if ($field === $newfield && !isset($data->$field) && isset($data->$legacyfield)) {
                    $value = $data->$legacyfield;
                }
            }

            $record->$field = self::normalize_value($field, $value);
        
        }
        return $record;
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

        $record = self::extract_from_form($data);
        $record->vplid = $vplid;

        if (!self::should_store($record)) {
            $DB->delete_records(self::TABLE, ['vplid' => $vplid]);
            return;
        }

        $record->usermodified = $usermodified;
        $record->timemodified = time();

        $existing = self::get_for_vpl($vplid);
        
        if ($existing) {

            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated ?? $record->timemodified;
            $DB->update_record(self::TABLE, $record);

        } else {

            $record->timecreated = $record->timemodified;
            $DB->insert_record(self::TABLE, $record);

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
        
        $DB->delete_records(self::TABLE, ['vplid' => $vplid]);
        $DB->delete_records(session_manager::TABLE, ['vplid' => $vplid]);
    }

    /**
     * Return true if a VPL instance uses SEB.
     *
     * @param \stdClass $instance VPL instance.
     * @return bool
     */
    public static function uses_seb(\stdClass $instance): bool {
        
        $record = self::get_effective_record($instance);
        
        return self::should_enforce_seb($record);
    }

    /**
     * Validate SEB access for a VPL instance.
     *
     * @param \stdClass $instance VPL instance.
     * @param string|null $fullme URL used to compute key hashes.
     * @param string|null $browserexamkey Optional browser exam key hash override.
     * @param string|null $configkeyhash Optional config key hash override.
     * @return bool
     */
    public static function validate_access(\stdClass $instance, ?string $fullme = null, ?string $browserexamkey = null, ?string $configkeyhash = null): bool {
        return access_validator::validate_access($instance, $fullme, $browserexamkey, $configkeyhash);
    }

    /**
     * Return a configuration hash derived from the manual SEB settings.
     *
     * @param \stdClass $record SEB settings record.
     * @return string
     */
    public static function get_config_hash(\stdClass $record): string {
        
        $payload = config_payload::canonicalize_payload(self::get_config_payload($record));
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            return '';
        }

        return hash('sha256', $json);
    }

    /**
     * Build a downloadable SEB configuration file from the stored settings.
     *
     * @param \stdClass $record SEB settings record.
     * @param $starturl URL that SEB should open.
     * @return string
     */
    public static function build_download_config_xml(\stdClass $record, $starturl, array $overrides = []): string {
        return plist_builder::build_download_config_xml($record, $starturl, $overrides);
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
     * Expand line-based filter expressions into plist rules.
     *
     * @param string $expressions Expressions separated by new lines.
     * @param bool $regex Whether the expressions are regular expressions.
     * @param int $action Rule action.
     * @return array<int, array{action:int, expression:string, regex:bool}>
     */
    protected static function build_filter_rules(string $expressions, bool $regex, int $action): array {
        return config_payload::build_filter_rules($expressions, $regex, $action);
    }

    /**
     * Check if the current config hash matches the stored manual settings.
     *
     * @param \stdClass $record SEB settings record.
     * @param string|null $configkeyhash Hash to compare.
     * @param string $fullme Current URL.
     * @return bool
     */
    public static function matches_config_hash(\stdClass $record, ?string $configkeyhash, string $fullme = ''): bool {
        return access_validator::matches_config_hash($record, $configkeyhash, $fullme);
    }

    /**
     * Check if the browser exam key hash matches one of the configured keys.
     *
     * @param \stdClass $record SEB settings record.
     * @param string|null $browserexamkey Hash to compare.
     * @param string $fullme Current URL.
     * @return bool
     */
    public static function matches_browser_exam_hash(\stdClass $record, ?string $browserexamkey, string $fullme): bool {
        return access_validator::matches_browser_exam_hash($record, $browserexamkey, $fullme);
    }

    /**
     * Return the effective settings record, falling back to VPL columns.
     *
     * @param \stdClass $instance VPL instance.
     * @return \stdClass
     */
    public static function get_effective_record(\stdClass $instance): \stdClass {
        
        $record = self::get_form_values_from_instance($instance);
        $record->vplid = (int)($instance->id ?? 0);
        $record->cmid = !empty($instance->cmid) ? (int)$instance->cmid : self::resolve_cmid($record->vplid);
        
        return $record;
    }

    /**
     * Determine whether the stored settings must enforce SEB.
     *
     * @param \stdClass $record SEB record.
     * @return bool
     */
    protected static function should_enforce_seb(\stdClass $record): bool {
        return !empty($record->requiresafeexambrowser) || trim((string)($record->allowedbrowserexamkeys ?? '')) !== '';
    }

    /**
     * Public bridge for helper classes that need enforce check by record.
     *
     * @param \stdClass $record SEB record.
     * @return bool
     */
    public static function should_enforce_for_record(\stdClass $record): bool {
        return self::should_enforce_seb($record);
    }

    /**
     * Determine whether the current configuration has custom SEB options.
     *
     * @param \stdClass $record SEB record.
     * @return bool
     */
    protected static function has_custom_config(\stdClass $record): bool {
        
        $defaults = self::get_defaults();
        
        foreach (self::get_config_fields() as $field) {
            
            $current = self::normalize_value($field, $record->$field ?? $defaults->$field);
            
            if ($current !== self::normalize_value($field, $defaults->$field)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return the payload used to compute the configuration hash.
     *
     * @param \stdClass $record SEB record.
     * @return array
     */
    protected static function get_config_payload(\stdClass $record): array {
        return config_payload::get_download_payload($record);
    }

    /**
     * Return the canonical payload used to hash the downloadable configuration.
     *
     * @param \stdClass $record SEB record.
     * @return array
     */
    protected static function get_download_payload(\stdClass $record): array {
        return config_payload::get_download_payload($record);
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
     * Resolve the course module id from a VPL instance id.
     *
     * @param int $vplid VPL instance id.
     * @return int
     */
    protected static function resolve_cmid(int $vplid): int {
        
        global $DB;

        if ($vplid <= 0) {
            return 0;
        }

        static $moduleid = null;
        
        if ($moduleid === null) {
            $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'vpl']);
        }

        if ($moduleid <= 0) {
            return 0;
        }

        return (int)$DB->get_field('course_modules', 'id', ['module' => $moduleid, 'instance' => $vplid]);
    }

    /**
     * Return the hashed quit password used in the downloadable configuration.
     *
     * @param \stdClass $record SEB record.
     * @return string
     */
    protected static function get_quit_password_hash(\stdClass $record): string {
        return config_payload::get_quit_password_hash($record);
    }

    /**
     * Return URL filter rules in canonical array form.
     *
     * @param \stdClass $record SEB record.
     * @return array
     */
    protected static function get_url_filter_rules(\stdClass $record): array {
        return config_payload::get_url_filter_rules($record);
    }

    /**
     * Should we store a SEB record for this VPL
     *
     * @param \stdClass $record SEB settings record.
     * @return bool
     */
    protected static function should_store(\stdClass $record): bool {
        
        $defaults = self::get_defaults();

        foreach (self::get_form_fields() as $field => $definition) {
            
            $current = self::normalize_value($field, $record->$field ?? $definition['default']);
            $default = self::normalize_value($field, $defaults->$field);
            
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
    protected static function normalize_value(string $field, $value) {
        
        if ($value === null) {
            $defaults = self::get_defaults();
            $value = $defaults->$field ?? null;
        }

        if (in_array($field, [ 'requiresafeexambrowser', 'showsebdownloadlink', 'enablesebsession', 'preventsebsimultaneoussessions', 'userconfirmquit', 'allowuserquitseb', 'allowreloadinexam', 'showsebtaskbar', 'showreloadbutton', 'showtime', 'showkeyboardlayout', 'showwificontrol', 'enableaudiocontrol', 'muteonstartup', 'allowcapturecamera', 'allowcapturemicrophone', 'allowspellchecking', 'activateurlfiltering', 'filterembeddedcontent', ], true)) {
            return (int)!empty($value);
        }

        return trim((string)$value);
    }
}

