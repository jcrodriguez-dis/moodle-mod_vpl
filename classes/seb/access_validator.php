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
 * SEB: Access validator for Safe Exam Browser integration.
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
 * Validate SEB request headers for VPL access.
 */
class access_validator {
    /**
     * Get the current SEB session and phase for a VPL.
     *
     * @param \stdClass $settings SEB settings record.
     * @return \stdClass Session and phase information.
     */
    public static function get_session_and_phase(\stdClass $settings): \stdClass {
        global $USER, $FULLME;
        $userid = empty($settings->enablesebsession) ? 0 : $USER->id;
        $fullme = $FULLME ?? '';
        $session = session_manager::get_session($settings, $userid);
        if (empty($session)) {
            // No existing session, create a new one and go to phase 0.
            $session = session_manager::create_session($settings, $userid);
            $session->phase = 0;
            return $session;
        }
        $session->phase = 0;
        $receivedconfigkey = trim(self::get_browser_config_key_from_request());
        if ($receivedconfigkey === '') {
            // Existing session, but no config key received, stay in phase 0.
            return $session;
        }
        $phase2exists = session_manager::exists_phase2($session);
        if ($phase2exists) {
            if (self::matches_config_key($receivedconfigkey, $session->configkey2, $fullme)) {
                // Phase 2 session exists and received config key matches.
                if (session_manager::is_unallowed_moodle_session($settings, $session)) {
                    // Moodle session is no longer allowed, log access denied.
                    // This must not happen under normal circumstances.
                    self::log_access_denied($settings, $userid, 'Moodle session lost SEB authentication');
                    $session->phase = -3;
                } else {
                    // Final access granted.
                    $session->phase = 2;
                }
                return $session;
            }
        }
        if (self::matches_config_key($receivedconfigkey, $session->configkey1, $fullme)) {
            // Phase 1 session matches the received config key.
            $session->phase = 1;
            if (session_manager::is_unallowed_moodle_session($settings, $session)) {
                // Moodle session is no longer allowed, log access denied.
                // Requires teacher intervention to regain access.
                self::log_access_denied($settings, $userid, 'Moodle session lost SEB authentication');
                $session->phase = -1;
            }
            return $session;
        }
        // Received config key does not match any existing session.
        // Keep the request in phase 0 and record the denied access attempt.
        self::log_access_denied($settings, $userid, 'SEB configuration key does not match session');
        return $session;
    }

    /**
     * Log a denied SEB access attempt.
     *
     * @param \stdClass $settings SEB settings record.
     * @param int $userid User id.
     * @param string $reason Denial reason.
     * @param array $other Additional event data.
     * @return void
     */
    protected static function log_access_denied(\stdClass $settings, int $userid, string $reason, array $other = []): void {
        $cmid = settings::get_cmid($settings->vplid);

        \mod_vpl\event\seb_access_denied::log([
            'objectid' => $settings->vplid,
            'context' => $cmid ? \context_module::instance($cmid) : \context_system::instance(),
            'userid' => $userid,
            'other' => array_merge([
                'reason' => $reason,
            ], $other),
        ]);
    }

    /**
     * Check if received key is valid with session key for the given url.
     *
     * @param string $receivedexamkey Received exam key from the browser.
     * @param string $sessionkey Session key to compare against.
     * @param string $url Current URL.
     * @return bool
     */
    public static function matches_config_key(string $receivedexamkey, string $sessionkey, string $url = ''): bool {
        if ($url === '') {
            global $FULLME;
            $url = $FULLME ?? '';
        }
        $expected = hash('sha256', $url . $sessionkey);
        return hash_equals($expected, strtolower($receivedexamkey));
    }

    /**
     * Get the browser config key from the request headers.
     * @return string
     */
    public static function get_browser_config_key_from_request(): string {
        $headername = 'HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH';
        if (isset($_SERVER[$headername])) {
            return trim($_SERVER[$headername]);
        }
        return '';
    }
}
