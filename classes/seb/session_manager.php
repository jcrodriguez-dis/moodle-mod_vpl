<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SEB: Per-user session configuration manager.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */

namespace mod_vpl\seb;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles the two-step anti fake SEB configuration flow.
 */
class session_manager {
    /** Table name for SEB session records. */
    public const TABLE = 'vpl_seb_session';

    /**
     * Return or create the per-user session.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param int $userid User id.
     * @param mixed $starturl Phase 1 start URL.
     * @return \stdClass
     */
    public static function get_or_create(\stdClass $record, int $userid, $starturl, bool $rotatephase1 = false): \stdClass {
        
        global $DB;

        $vplid = (int)$record->vplid;
        $session = $DB->get_record(self::TABLE, ['vplid' => $vplid, 'userid' => $userid]);

        if ($session) {
            $samesession = !empty($session->sesskey) && hash_equals((string)$session->sesskey, sesskey());

            if ($rotatephase1 && !$samesession) {
                $session->token1public = self::new_token($vplid, $userid, sesskey());
                $session->token1private = self::new_token($vplid, $userid, sesskey());
            }

            $session->configkey1 = self::calculate_config_key($record, $starturl, (string)$session->token1private, true);
            $DB->update_record(self::TABLE, $session);

            return $session;
        }

        $session = (object)[
            'vplid' => $vplid,
            'userid' => $userid,
            'token1public' => self::new_token($vplid, $userid, sesskey()),
            'token1private' => self::new_token($vplid, $userid, sesskey()),
            'configkey1' => '',
            'sesskey' => '',
            'token2private' => '',
            'configkey2' => '',
        ];

        $session->configkey1 = self::calculate_config_key($record, $starturl, $session->token1private, true);
        $session->id = $DB->insert_record(self::TABLE, $session);

        return $session;
    }



     /**
     * Centralized validation for SEB token. Triggers event if invalid.
     *
     * @param string $token Public token.
     * @param int $vplid VPL id.
     * @param int $userid User id.
     * @return null|object Session if valid, null if invalid (and logs event)
     */
    public static function validate_token($token, $vplid, $userid) {
        global $USER;
        $session = self::get_by_public_token($token);
        if (!$session || (int)$session->vplid !== (int)$vplid || (int)$session->userid !== (int)$userid) {
            // Disparar evento de clave SEB incorrecta.
            $cmid = \mod_vpl\seb\settings::resolve_cmid($vplid);
            \mod_vpl\event\seb_wrong_key::create([
                'objectid' => $vplid,
                'context' => $cmid ? \context_module::instance($cmid) : null,
                'userid' => $USER->id,
                'other' => [
                    'token' => $token
                ]
            ])->trigger();
            return null;
        }
        return $session;
    }
    
    /**
     * Return a session record by the public phase 1 token.
     *
     * @param string $token Public token.
     * @return \stdClass|null
     */
    public static function get_by_public_token(string $token): ?\stdClass {
        
        global $DB;

        $session = $DB->get_record(self::TABLE, ['token1public' => $token]);

        return $session === false ? null : $session;
    }

    /**
     * Refresh the stored phase 1 config key for an existing token record.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param \stdClass $session Session record.
     * @param mixed $starturl Start URL.
     * @return \stdClass
     */
    public static function refresh_phase1(\stdClass $record, \stdClass $session, $starturl): \stdClass {
        
        global $DB;

        $session->configkey1 = self::calculate_config_key($record, $starturl, (string)$session->token1private, true);
        
        if (!empty($session->id)) {
            $DB->update_record(self::TABLE, $session);
        }
        return $session;
    }

    /**
     * Build the phase 1 SEB configuration.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param \stdClass $session Session record.
     * @param mixed $starturl Start URL.
     * @return string
     */
    public static function build_phase1_config_xml(\stdClass $record, \stdClass $session, $starturl): string {
        
        $phase1 = self::get_phase1_record($record);
        
        return settings::build_download_config_xml(
            $phase1,
            $starturl,
            [
                'downloadAndOpenSebConfig' => true,
                'examSessionClearCookiesOnEnd' => false,
                'examSessionClearCookiesOnStart' => true,
                'examSessionReconfigureAllow' => true,
                'examSessionReconfigureConfigURL' => '*',
                'examKeySalt' => (string)$session->token1private,
                'removeBrowserProfile' => false,
            ]
        );
    }

    /**
     * Build the phase 2 SEB configuration.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param \stdClass $session Session record.
     * @param mixed $starturl Start URL.
     * @return string
     */
    public static function build_phase2_config_xml(\stdClass $record, \stdClass $session, $starturl): string {
        return settings::build_download_config_xml(
            $record,
            $starturl,
            [
                'downloadAndOpenSebConfig' => false,
                'examSessionClearCookiesOnEnd' => true,
                'examSessionClearCookiesOnStart' => false,
                'examSessionReconfigureAllow' => false,
                'examSessionReconfigureConfigURL' => '',
                'examKeySalt' => (string)$session->token2private,
                'removeBrowserProfile' => true,
            ]
        );
    }

    /**
     * Return true when the current request is using the user's phase 1 config key.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param int $userid User id.
     * @param string $fullme Current URL.
     * @param string|null $configkeyhash Optional received hash.
     * @return bool
     */
    public static function is_phase1_request( \stdClass $record, int $userid, string $fullme, ?string $configkeyhash = null ): bool {

        if (empty($record->enablesebsession)) {
            return false;
        }

        return self::get_phase1_request_session($record, $userid, $fullme, $configkeyhash) !== null;
    }

    /**
     * Return the phase 1 record matching the current SEB request.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param int $userid User id.
     * @param string $fullme Current URL.
     * @param string|null $configkeyhash Optional received hash.
     * @return \stdClass|null
     */
    protected static function get_phase1_request_session( \stdClass $record, int $userid, string $fullme, ?string $configkeyhash = null ): ?\stdClass {

        global $DB;

        $sessions = $DB->get_records(self::TABLE, ['vplid' => (int)$record->vplid, 'userid' => $userid], 'id DESC');
        
        foreach ($sessions as $session) {
            if (empty($session->configkey1)) {
                continue;
            }
            if (self::matches_config_key( (string)$session->configkey1, $fullme, $configkeyhash, [config_payload::get_start_url($record)] )) {
                return $session;
            }
        }

        return null;
    }

    /**
     * Prepare phase 2 for the current Moodle session.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param int $userid User id.
     * @param mixed $starturl Phase 2 start URL.
     * @return \stdClass
     */
    public static function start_phase2( \stdClass $record, int $userid, $starturl, string $fullme = '', bool $replaceothers = false ): \stdClass {

        global $DB;

        $session = self::get_phase1_request_session($record, $userid, $fullme);

        if (!$session) {
            $session = self::get_or_create($record, $userid, $starturl);
        }

        if ($replaceothers && !empty($session->id)) {
            $DB->delete_records_select( self::TABLE, 'vplid = :vplid AND userid = :userid AND id <> :id', ['vplid' => (int)$record->vplid, 'userid' => $userid, 'id' => $session->id] );
        }

        $session->sesskey = sesskey();
        $session->token2private = self::new_token((int)$record->vplid, $userid, $session->sesskey);
        $session->configkey2 = self::calculate_config_key($record, $starturl, $session->token2private, false);
        $DB->update_record(self::TABLE, $session);
        
        return $session;
    }

    /**
     * Return true if another Moodle session is already registered for this user/activity.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param int $userid User id.
     * @return bool
     */
    public static function has_other_active_session(\stdClass $record, int $userid): bool {
        
        global $DB;

        if (empty($record->preventsebsimultaneoussessions)) {
            return false;
        }
        
        $sessions = $DB->get_records(self::TABLE, ['vplid' => (int)$record->vplid, 'userid' => $userid]);
        
        foreach ($sessions as $session) {
            if (!empty($session->sesskey) && !hash_equals((string)$session->sesskey, sesskey())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate final SEB access against phase 2 config key and Moodle session key.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param int $userid User id.
     * @param string $fullme Current URL.
     * @param string|null $configkeyhash Optional received hash.
     * @return bool
     */
    public static function validate_final_access( \stdClass $record, int $userid, string $fullme, ?string $configkeyhash = null ): bool {
        
        global $DB;

        $sessions = $DB->get_records(self::TABLE, ['vplid' => (int)$record->vplid, 'userid' => $userid], 'id DESC');
        
        foreach ($sessions as $session) {
            if (empty($session->configkey2)) {
                continue;
            }
            if (!self::matches_config_key( (string)$session->configkey2, $fullme, $configkeyhash, [config_payload::get_start_url($record)] )) {
                continue;
            }
            if (!empty($record->preventsebsimultaneoussessions) && !hash_equals((string)$session->sesskey, sesskey())) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Check a plain teacher exception password.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param string $password Submitted password.
     * @return bool
     */
    public static function validate_teacher_password(\stdClass $record, string $password): bool {
        
        $expected = trim((string)($record->sebteacherpassword ?? ''));
        
        return $expected !== '' && hash_equals($expected, $password);
    }

    /**
     * Calculate the stored SEB configuration key for the generated XML payload.
     *
     * @param \stdClass $record Effective SEB settings.
     * @param mixed $starturl Start URL.
     * @param string $examkeysalt Dynamic examKeySalt value.
     * @param bool $phase1 Whether to calculate the permissive phase 1 key.
     * @return string
     */
    protected static function calculate_config_key( \stdClass $record, $starturl, string $examkeysalt, bool $phase1 ): string {

        $payloadrecord = $phase1 ? self::get_phase1_record($record) : clone($record);
        $payload = config_payload::get_download_payload($payloadrecord);
        $payload['startURL'] = self::starturl_to_string($starturl);
        $payload['downloadAndOpenSebConfig'] = $phase1;
        $payload['examSessionClearCookiesOnEnd'] = !$phase1;
        $payload['examSessionClearCookiesOnStart'] = $phase1;
        $payload['examSessionReconfigureAllow'] = $phase1;
        $payload['examSessionReconfigureConfigURL'] = $phase1 ? '*' : '';
        $payload['examKeySalt'] = $examkeysalt;
        $payload['removeBrowserProfile'] = !$phase1;
        $payload = config_payload::canonicalize_payload($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Return a permissive SEB record for the authentication phase.
     *
     * @param \stdClass $record Effective SEB settings.
     * @return \stdClass
     */
    protected static function get_phase1_record(\stdClass $record): \stdClass {
        
        $phase1 = settings::get_defaults();
        $phase1->vplid = (int)($record->vplid ?? 0);
        $phase1->cmid = (int)($record->cmid ?? 0);
        $phase1->requiresafeexambrowser = 1;
        $phase1->showsebdownloadlink = 1;
        $phase1->linkquitseb = (string)($record->linkquitseb ?? '');
        $phase1->userconfirmquit = (int)!empty($record->userconfirmquit);
        $phase1->allowuserquitseb = (int)!empty($record->allowuserquitseb);
        $phase1->quitpassword = (string)($record->quitpassword ?? '');
        $phase1->adminpassword = (string)($record->adminpassword ?? '');
        $phase1->allowreloadinexam = 1;
        $phase1->showsebtaskbar = 1;
        $phase1->showreloadbutton = 1;
        $phase1->showtime = 1;
        $phase1->showkeyboardlayout = 1;
        $phase1->allowedbrowserexamkeys = '';

        return $phase1;
    }

    /**
     * Compare a received SEB ConfigKeyHash with a stored config key.
     *
     * @param string $configkey Stored config key.
     * @param string $fullme Current URL.
     * @param string|null $configkeyhash Optional received hash.
     * @param string[] $extraurls Extra URLs accepted for the same config key.
     * @return bool
     */
    protected static function matches_config_key( string $configkey, string $fullme, ?string $configkeyhash = null, array $extraurls = [] ): bool {

        $received = trim((string)($configkeyhash ?? self::get_config_key_header()));
        
        if ($received === '') {
            return false;
        }

        foreach (array_merge([$fullme], $extraurls) as $candidateurl) {
            
            $url = $candidateurl;
            
            if ($url === '') {
                continue;
            }

            if (hash_equals(hash('sha256', $url . $configkey), strtolower($received))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the SEB ConfigKeyHash request header.
     *
     * @return string
     */
    protected static function get_config_key_header(): string {
        
        foreach ([ 'HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH', 'X-SafeExamBrowser-ConfigKeyHash', 'X_SAFEEXAMBROWSER_CONFIGKEYHASH', ] as $name) {
            if (!empty($_SERVER[$name])) {
                return (string)$_SERVER[$name];
            }
        }
        return '';
    }

    /**
     * Create a new opaque SHA-256 token.
     *
     * @param int $vplid VPL id.
     * @param int $userid User id.
     * @param string $extra Extra entropy.
     * @return string
     */
    protected static function new_token(int $vplid, int $userid, string $extra = ''): string {
        
        $message = implode('|', [ $vplid, $userid, $extra, random_string(64), ]);

        return hash_hmac('sha256', $message, self::get_site_secret());
    }

    /**
     * Return a stable site secret for deriving SEB session tokens.
     *
     * @return string
     */
    protected static function get_site_secret(): string {
        
        global $CFG;

        foreach (['passwordsaltmain', 'dbpass', 'dataroot'] as $field) {
            if (!empty($CFG->$field)) {
                return (string)$CFG->$field;
            }
        }

        return (string)($CFG->wwwroot ?? __FILE__);
    }

    /**
     * Convert a moodle_url/string start URL to the exact XML string representation.
     *
     * @param mixed $starturl Start URL.
     * @return string
     */
    protected static function starturl_to_string($starturl): string {
        if (is_object($starturl) && method_exists($starturl, 'out')) {
            return $starturl->out(true);
        }
        
        return (string)$starturl;
    }
}
