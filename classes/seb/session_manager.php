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
 * SEB: Session manager for Safe Exam Browser integration.
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
 * Handles the two-step SEB configuration flow.
 * Requires two steps to allow the student to login to Moodle before accessing the activity.
 *
 * First step (option a):
 *      The student goes to the VPL activity in Moodle.
 *      The student clicks on the SEB launch link in Moodle.
 *      Seb browser downloads a permissive SEB configuration for that user.
 * First step (option b):
 *     The student goes to the VPL activity in Moodle.
 *     The student downloads or gets the permissive SEB configuration.
 *     The users double clicks the SEB configuration file to launch SEB.
 *
 * This permissive configuration allows the student to login in Moodle.
 *
 * Second step:
 *     The student goes to the activity.
 *     If multissesion control is enabled and user already has a session,
 *     the teacher's password for a new session is required.
 *     Then automatically SEB browser downloads a restrictive SEB configuration for that activity.
 *     This restrictive configuration allows the student to access the activity.
 */
class session_manager {
    /** Table name for SEB session records. */
    public const TABLE = 'vpl_seb_session';

    /**
     * Get record with VPL-SEB user session.
     *
     * @param \stdClass $settings SEB settings.
     * @param int $userid User id.
     * @return ?\stdClass Session record or null if not found.
     */
    public static function get_session(\stdClass $settings, int $userid): ?\stdClass {
        global $DB;
        if (empty($settings->enablesebsession)) {
            $userid = 0;
        }
        $vplid = $settings->vplid;
        $params = ['vplid' => $vplid, 'userid' => $userid];
        $session = $DB->get_record(self::TABLE, $params);
        return !empty($session) ? $session : null;
    }

    /**
     * Creates a new session that can be specific for a user or shared by activity.
     *
     * @param \stdClass $settings SEB settings.
     * @param int $userid User id.
     * @return \stdClass The new session record.
     */
    public static function create_session(\stdClass $settings, int $userid): \stdClass {
        global $DB;
        if (empty($settings->enablesebsession)) {
            $userid = 0;
        }
        $vplid = $settings->vplid;
        $session = (object)[
            'vplid' => $vplid,
            'userid' => $userid,
            'token1public' => self::new_token($vplid, $userid),
            'token1private' => self::new_token($vplid, $userid),
            'token2private' => '',
            'configkey1' => '',
            'configkey2' => '',
            'sesskey' => '',
        ];
        $payload = config_payload::get_phase1_payload($settings, $session->token1private);
        $session->configkey1 = self::calculate_config_key($payload);
        try {
            $session->id = $DB->insert_record(self::TABLE, $session);
        } catch (\dml_exception $e) {
            // Handle race condition where another session was created for the same user and VPL.
            $session = self::get_session($settings, $userid);
            if (empty($session)) {
                throw $e;
            }
        }
        return $session;
    }

    /**
     * Creates a new session that can be specific for a user or shared by activity.
     *
     * @param \stdClass $settings SEB settings.
     * @param int $userid User id.
     * @return \stdClass The new session record.
     */
    public static function get_or_create_session(\stdClass $settings, int $userid): \stdClass {
        $session = self::get_session($settings, $userid);
        if (empty($session)) {
            $session = self::create_session($settings, $userid);
        }
        return $session;
    }

    /**
     * Update an existing session record.
     *
     * @param \stdClass $session Session record.
     * @return void
     */
    public static function update_session(\stdClass $session): void {
        global $DB;
        $DB->update_record(self::TABLE, $session);
    }

    /**
     * Does the given SEB session have phase 2 data?
     *
     * @param \stdClass $session Session record.
     * @return bool True if the session has phase 2 data.
     */
    public static function exists_phase2(\stdClass $session): bool {
        return !empty($session->configkey2) && !empty($session->token2private);
    }

    /**
     * Is the given SEB session not allowing current Moodle session?
     *
     * @param \stdClass $settings SEB settings.
     * @param \stdClass $session Session record.
     * @return bool True if SEB session does not allow current Moodle sessions.
     */
    public static function is_unallowed_moodle_session(\stdClass $settings, \stdClass $session): bool {
        if (empty($session) || empty($session->sesskey)) {
            return false;
        }
        if ($settings->enablesebsession && $settings->preventsebsimultaneoussessions) {
            return !hash_equals($session->sesskey, sesskey());
        }
        return false;
    }

    /**
     * Prepare SEB session for phase 2 and if necessary associate it to current Moodle session.
     *
     * @param \stdClass $settings SEB settings.
     * @param \stdClass $session Session record.
     * @return void
     */
    public static function prepare_phase2(\stdClass $settings, \stdClass $session): void {
        if ($settings->enablesebsession && $settings->preventsebsimultaneoussessions) {
            $session->sesskey = sesskey();
        }
        $session->token2private = self::new_token($settings->vplid, $session->userid);
        $payload = config_payload::get_phase2_payload($settings, $session->token2private);
        $session->configkey2 = self::calculate_config_key($payload);
        self::update_session($session);
    }

    /**
     * Get session using public token and validate it. Triggers event if invalid.
     *
     * @param string $token Public token.
     * @param int $vplid VPL id.
     * @return null|object Session if valid, null if invalid (and logs event)
     */
    public static function get_session_by_public_token(string $token, int $vplid) {
        global $DB;
        global $USER;
        $session = $DB->get_record(self::TABLE, ['token1public' => $token, 'vplid' => $vplid]);
        if (empty($session)) {
            // Log event for invalid token access attempt.
            $cmid = settings::get_cmid($vplid);
            $info = [
                'objectid' => $vplid,
                'context' => $cmid ? \context_module::instance($cmid) : \context_system::instance(),
                'userid' => $USER->id ?? 0,
                'other' => ['reason' => 'No session found for token'],
            ];
            \mod_vpl\event\seb_wrong_key::log($info);
        }
        return $session;
    }

    /**
     * Get phase1 SEB configuration XML for a user session.
     * @param \stdClass $settings SEB settings.
     * @param \stdClass $session Session record.
     * @return string The SEB configuration XML.
     */
    public static function get_phase1_config_xml(\stdClass $settings, \stdClass $session): string {
        $payload = config_payload::get_phase1_payload($settings, $session->token1private);
        $configkey = self::calculate_config_key($payload);
        if (!hash_equals($session->configkey1, $configkey)) {
            // The payload changed since the session was created, keep the stored key in sync.
            $session->configkey1 = $configkey;
            self::update_session($session);
        }
        return plist_builder::build_config_xml($payload);
    }

    /**
     * Get phase2 SEB configuration XML for a user session.
     * @param \stdClass $settings SEB settings.
     * @param \stdClass $session Session record.
     * @return string The SEB configuration XML.
     */
    public static function get_phase2_config_xml(\stdClass $settings, \stdClass $session): string {
        $payload = config_payload::get_phase2_payload($settings, $session->token2private);
        $configkey = self::calculate_config_key($payload);
        if (!hash_equals($session->configkey2, $configkey)) {
            // The payload changed since phase 2 was prepared, keep the stored key in sync.
            $session->configkey2 = $configkey;
            self::update_session($session);
        }
        return plist_builder::build_config_xml($payload);
    }

    /**
     * Check a plain teacher exception password.
     *
     * Note: The password is stored in the database as plain text.
     * This allows to reveal the password for the teacher in the activity view page.
     * This password allows teachers to accept a new session for a student.
     * We will use hash_equals to avoid timing attacks.
     * @param \stdClass $settings SEB settings.
     * @param string $password Submitted password.
     * @return bool true if the password is correct.
     */
    public static function validate_teacher_password(\stdClass $settings, string $password): bool {
        $expected = trim((string)($settings->sebteacherpassword ?? ''));
        return $expected !== '' && hash_equals($expected, $password);
    }

    /**
     * Calculate the stored SEB configuration key for the generated XML payload.
     *
     * @param array $payload Payload of the SEB configuration.
     * @return string
     */
    protected static function calculate_config_key($payload): string {
        return hash('sha256', config_payload::get_seb_json($payload));
    }

    /**
     * Create a new opaque SHA-256 token.
     *
     * @param int $vplid VPL id.
     * @param int $userid User id.
     * @return string
     */
    protected static function new_token(int $vplid, int $userid): string {
        [$minutes, $nanoseconds] = hrtime(); // Add some entropy.
        $message = implode('|', [$nanoseconds, $vplid, $userid, complex_random_string(64), $minutes]);
        return hash('sha256', $message);
    }

    /**
     * Delete sessions for a VPL instance.
     *
     * @param int $vplid VPL instance id.
     * @return void
     */
    public static function delete_for_vpl(int $vplid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['vplid' => $vplid]);
    }

    /**
     * Check whether a VPL has any stored SEB sessions.
     *
     * @param int $vplid VPL instance id.
     * @return bool True when at least one session exists.
     */
    public static function exists_for_vpl(int $vplid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['vplid' => $vplid]);
    }

    /**
     * Send a SEB configuration file and stop script.
     *
     * @param string $config SEB plist XML.
     * @return void
     */
    public static function send_seb_config($config) {
        $headers = [
            'Cache-Control: private, no-store, max-age=0',
            'Expires: 0',
            'Pragma: no-cache',
            'Content-Disposition: attachment; filename=' . settings::get_download_filename(),
            'Content-Type: application/seb',
            'Content-Length: ' . strlen($config),
        ];
        foreach ($headers as $header) {
            header($header);
        }
        echo $config;
        die();
    }
}
