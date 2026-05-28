<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SEB: Config payload builder for Safe Exam Browser integration.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */

namespace mod_vpl\seb;

defined('MOODLE_INTERNAL') || die();

/**
 * Build canonical SEB payload
 */
class config_payload {
    /**
     * Build canonical download payload from SEB.
     *
     * @param \stdClass $record SEB record.
     * @return array
     */
    public static function get_download_payload(\stdClass $record): array {
        
        $payload = [];
        $payload['showTaskBar'] = !empty($record->showsebtaskbar);
        $payload['allowWlan'] = !empty($record->showwificontrol);
        $payload['showReloadButton'] = !empty($record->showreloadbutton);
        $payload['showTime'] = !empty($record->showtime);
        $payload['showInputLanguage'] = !empty($record->showkeyboardlayout);
        $payload['allowQuit'] = !empty($record->allowuserquitseb);

        $quiturl = trim((string)($record->linkquitseb ?? ''));
        
        if ($quiturl !== '') {
            $payload['quitURL'] = $quiturl;
        }

        $payload['quitURLConfirm'] = !empty($record->userconfirmquit);
        $payload['audioControlEnabled'] = !empty($record->enableaudiocontrol);
        $payload['audioMute'] = !empty($record->muteonstartup);
        $payload['browserMediaCaptureCamera'] = !empty($record->allowcapturecamera);
        $payload['browserMediaCaptureMicrophone'] = !empty($record->allowcapturemicrophone);
        $payload['allowSpellCheck'] = !empty($record->allowspellchecking);
        $payload['browserWindowAllowReload'] = !empty($record->allowreloadinexam);
        $payload['URLFilterEnable'] = !empty($record->activateurlfiltering);
        $payload['URLFilterEnableContentFilter'] = !empty($record->filterembeddedcontent);
        $payload['URLFilterRules'] = self::get_url_filter_rules($record);
        $payload['startURL'] = self::get_start_url($record);
        $payload['sendBrowserExamKey'] = true;
        $payload['browserWindowWebView'] = 3;
        $payload['examSessionClearCookiesOnStart'] = false;
        $payload['allowPreferencesWindow'] = false;

        $quitpasswordhash = self::get_quit_password_hash($record);
        
        if ($quitpasswordhash !== '') {
            $payload['hashedQuitPassword'] = $quitpasswordhash;
        }

        $adminpasswordhash = self::get_admin_password_hash($record);
        
        if ($adminpasswordhash !== '') {
            $payload['hashedAdminPassword'] = $adminpasswordhash;
        }

        return $payload;
    }

    /**
     * Return VPL start URL used in SEB payload.
     *
     * @param \stdClass $record SEB record.
     * @return string
     */
    public static function get_start_url(\stdClass $record): string {
        
        global $CFG;

        $cmid = (int)($record->cmid ?? 0);
        
        if ($cmid <= 0) {
            $cmid = self::resolve_cmid((int)($record->vplid ?? 0));
        }

        $id = $cmid > 0 ? $cmid : (int)($record->vplid ?? 0);
        
        return $CFG->wwwroot . '/mod/vpl/view.php?id=' . $id;
    }

    /**
     * Return URL filter rules.
     *
     * @param \stdClass $record SEB record.
     * @return array
     */
    public static function get_url_filter_rules(\stdClass $record): array {
        return array_merge(
            self::build_filter_rules((string)($record->expressionsallowed ?? ''), !empty($record->regexallowed), 1),
            self::build_filter_rules((string)($record->expressionsblocked ?? ''), !empty($record->regexblocked), 0)
        );
    }

    /**
     * Line-based filter expressions into rules.
     *
     * @param string $expressions Expressions separated by line breaks.
     * @param bool $regex Whether expressions are regular expressions.
     * @param int $action Rule action.
     * @return array<int, array{action:int, expression:string, regex:bool}>
     */
    public static function build_filter_rules(string $expressions, bool $regex, int $action): array {
        $result = [];
        
        foreach (preg_split('/\R+/', trim($expressions)) ?: [] as $expression) {
            
            $expression = trim($expression);
            
            if ($expression === '') {
                continue;
            }

            $result[] = [ 'action' => $action, 'expression' => $expression, 'regex' => $regex, ];
        }

        return $result;
    }

    /**
     * Return hashed quit password.
     *
     * @param \stdClass $record SEB record.
     * @return string
     */
    public static function get_quit_password_hash(\stdClass $record): string {
        
        if (empty($record->allowuserquitseb)) {
            return '';
        }

        $quitpassword = trim((string)($record->quitpassword ?? ''));
        
        if ($quitpassword === '') {
            return '';
        }

        return hash('sha256', $quitpassword);
    }

    /**
     * Return hashed administrator password.
     *
     * @param \stdClass $record SEB record.
     * @return string
     */
    public static function get_admin_password_hash(\stdClass $record): string {
        
        $adminpassword = trim((string)($record->adminpassword ?? ''));
        
        if ($adminpassword === '') {
            return '';
        }

        return hash('sha256', $adminpassword);
    }

    /**
     * Canonicalize payload recursively for deterministic key order.
     *
     * @param mixed $value Value to canonicalize.
     * @return mixed
     */
    public static function canonicalize_payload($value) {
        
        if (!is_array($value)) {
            return $value;
        }

        if (self::is_assoc_array($value)) {
            
            $normalized = [];
            
            foreach ($value as $key => $item) {
                
                $canonical = self::canonicalize_payload($item);
                
                if (is_array($canonical) && self::is_assoc_array($canonical) && $canonical === []) {
                    continue;
                }

                $normalized[(string)$key] = $canonical;
            
                }

            uksort($normalized, static function(string $a, string $b): int { return strcasecmp($a, $b); });

            return $normalized;
        }

        return array_map([self::class, 'canonicalize_payload'], $value);
    }

    /**
     * Return true when array has string keys.
     *
     * @param array $value Array to inspect.
     * @return bool
     */
    public static function is_assoc_array(array $value): bool {
        
        if ($value === []) {
            return false;
        }
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * Resolve course module id from VPL instance id.
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
}

