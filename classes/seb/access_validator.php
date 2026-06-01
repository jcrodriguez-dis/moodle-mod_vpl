<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SEB: Access validator for Safe Exam Browser integration.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */

namespace mod_vpl\seb;

defined('MOODLE_INTERNAL') || die();

/**
 * Validate SEB request headers for VPL access.
 */
class access_validator {
    /**
     * Validate SEB access for a VPL instance.
     *
     * @param \stdClass $instance VPL instance.
     * @param string|null $fullme Current URL.
     * @param string|null $browserexamkey Optional browser exam key hash override.
     * @param string|null $configkeyhash Optional config key hash override.
     * @return bool
     */
    public static function validate_access(\stdClass $instance, ?string $fullme = null, ?string $browserexamkey = null, ?string $configkeyhash = null): bool {
        
        global $USER;

        $record = settings::get_effective_record($instance);
        $fullme = $fullme ?? ($GLOBALS['FULLME'] ?? '');
        $userid = (int)($USER->id ?? 0);
        
        if (!settings::should_enforce_for_record($record)) {
            return true;
        }

        if (!empty($record->enablesebsession)) {
            if ($userid <= 0) {
                self::log_access_denied($record, $userid, 'missing_user');
                return false;
            }

            $allowed = session_manager::validate_final_access($record, $userid, $fullme, $configkeyhash);

            if (!$allowed) {
                self::log_access_denied($record, $userid, 'invalid_seb_session');
            }

            return $allowed;
        }

        $keys = trim((string)($record->allowedbrowserexamkeys ?? ''));

        $receivedconfigkey = trim((string)($configkeyhash ?? self::get_request_header([ 'X-SafeExamBrowser-ConfigKeyHash' ])));

        if (!self::matches_config_hash($record, $receivedconfigkey, $fullme)) {
            self::log_access_denied($record, $userid, 'invalid_config_hash', ['hasbrowserexamkeys' => $keys !== '']);
            return false;
        }

        if ($keys === '') {
            return true;
        }

        $receivedbrowserexamkey = trim((string)($browserexamkey ?? self::get_request_header([ 'X-SafeExamBrowser-RequestHash' ])));

        $allowed = self::matches_browser_exam_hash($record, $receivedbrowserexamkey, $fullme);

        if (!$allowed) {
            self::log_access_denied($record, $userid, 'invalid_browser_exam_hash');
        }

        return $allowed;
    }

    /**
     * Log a denied SEB access attempt.
     *
     * @param \stdClass $record SEB settings record.
     * @param int $userid User id.
     * @param string $reason Denial reason.
     * @param array $other Additional event data.
     * @return void
     */
    protected static function log_access_denied(\stdClass $record, int $userid, string $reason, array $other = []): void {
        $cmid = !empty($record->cmid) ? (int)$record->cmid : settings::resolve_cmid((int)($record->vplid ?? 0));

        \mod_vpl\event\seb_access_denied::create([
            'objectid' => (int)($record->vplid ?? 0),
            'context' => $cmid ? \context_module::instance($cmid) : \context_system::instance(),
            'userid' => $userid,
            'other' => array_merge([
                'reason' => $reason,
            ], $other),
        ])->trigger();
    }

    /**
     * Check if received config hash is valid.
     *
     * @param \stdClass $record SEB settings record.
     * @param string|null $configkeyhash Hash to compare.
     * @param string $fullme Current URL.
     * @return bool
     */
    public static function matches_config_hash(\stdClass $record, ?string $configkeyhash, string $fullme = ''): bool {
        if (!$configkeyhash) {
            return false;
        }

        $url = $fullme;
        
        if ($url === '') {
            return false;
        }

        $confighash = settings::get_config_hash($record);
        
        if ($confighash === '') {
            return false;
        }

        $expected = hash('sha256', $url . $confighash);
        return hash_equals($expected, strtolower($configkeyhash));
    }

    /**
     * Check if received browser exam hash is valid.
     *
     * @param \stdClass $record SEB settings record.
     * @param string|null $browserexamkey Hash to compare.
     * @param string $fullme Current URL.
     * @return bool
     */
    public static function matches_browser_exam_hash(\stdClass $record, ?string $browserexamkey, string $fullme): bool {
        
        $keys = trim((string)($record->allowedbrowserexamkeys ?? ''));
        
        if ($keys === '') {
            return true;
        }
        if (!$browserexamkey) {
            return false;
        }

        $url = $fullme;
        
        if ($url === '') {
            return false;
        }

        foreach (preg_split('/\s+/', $keys) as $candidate) {
            
            if ($candidate === '') {
                continue;
            }

            if (hash('sha256', $url . $candidate) === strtolower($browserexamkey)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return first non-empty request header
     *
     * @param string[] $names Candidate header names.
     * @return string
     */
    protected static function get_request_header(array $names): string {
        foreach ($names as $name) {
            if (!empty($_SERVER[$name])) {
                return (string)$_SERVER[$name];
            }
        }

        if (function_exists('getallheaders')) {
            
            $headers = getallheaders();
            
            if (is_array($headers)) {
                
                $normalized = [];
                
                foreach ($headers as $key => $value) {
                    $normalized[strtolower((string)$key)] = (string)$value;
                }

                foreach ($names as $name) {
                    
                    $lookup = strtolower(str_replace('HTTP_', '', str_replace('_', '-', $name)));
                    
                    if (!empty($normalized[$lookup])) {
                        return $normalized[$lookup];
                    }
                }
            }
        }

        return '';
    }
}



