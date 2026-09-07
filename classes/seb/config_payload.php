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
 * SEB: Config payload builder for Safe Exam Browser integration.
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
 * Build canonical SEB payload
 */
class config_payload {
    /**
     * Improbable string used to keep literal backslashes unescaped in the SEB-JSON string.
     * json_encode() always escapes backslashes, so they are substituted before encoding and restored after.
     */
    const BACKSLASH_SUBSTITUTE = "\u{0BF9}\u{0DF4}\u{FFFD}\u{0BF9}";

    /**
     * Default payload for SEB configuration.
     */
    const DEFAULT_PAYLOAD = [
        'showTaskBar' => true,
        'allowWlan' => true,
        'showReloadButton' => true,
        'showTime' => true,
        'showInputLanguage' => true,
        'allowQuit' => true,
        'quitURLConfirm' => false,
        'audioControlEnabled' => false,
        'audioMute' => false,
        'browserMediaCaptureCamera' => false,
        'browserMediaCaptureMicrophone' => false,
        'allowSpellCheck' => false,
        'browserWindowAllowReload' => true,
        'URLFilterEnable' => false,
        'URLFilterEnableContentFilter' => false,
        'URLFilterRules' => [],
        'startURL' => '',
        'sendBrowserExamKey' => true,
        'browserWindowWebView' => 3,
        'examSessionClearCookiesOnStart' => false,
        'allowPreferencesWindow' => false,
        'downloadAndOpenSebConfig' => false,
        'examSessionClearCookiesOnEnd' => false,
        'examSessionReconfigureAllow' => false,
        'removeBrowserProfile' => false,
    ];

    /**
     * Build phase 1 payload for SEB.
     *
     * @param \stdClass $settings SEB record.
     * @param string $secret Secret for avoid easy open of config with SEB configuration Tool.
     * @return array
     */
    public static function get_phase1_payload(\stdClass $settings, $secret): array {
        $payload = self::DEFAULT_PAYLOAD;
        $payload['startURL'] = self::get_start_url($settings);
        $payload['hashedAdminPassword'] = self::get_password_hash($secret);
        $payload['downloadAndOpenSebConfig'] = true;
        // Discard any cookie kept from a previous run, so Moodle login is always required.
        $payload['examSessionClearCookiesOnStart'] = true;
        // Nothing may be cleared on end: this session ends when reconfiguring to phase 2,
        // which must inherit the Moodle login done here.
        $payload['examSessionClearCookiesOnEnd'] = false;
        $payload['removeBrowserProfile'] = false;
        // Allow reconfiguration to phase 2.
        $payload['examSessionReconfigureAllow'] = true;
        $payload['examSessionReconfigureConfigURL'] = self::get_start_url($settings);
        return $payload;
    }

    /**
     * Build phase 2 payload for SEB.
     *
     * @param \stdClass $settings SEB record.
     * @param string $secret Secret for avoid easy open of config with SEB configuration Tool.
     * @return array
     */
    public static function get_phase2_payload(\stdClass $settings, $secret): array {
        $payload = self::DEFAULT_PAYLOAD;
        $payload['showTaskBar'] = !empty($settings->showsebtaskbar);
        $payload['allowWlan'] = !empty($settings->showwificontrol);
        $payload['showReloadButton'] = !empty($settings->showreloadbutton);
        $payload['showTime'] = !empty($settings->showtime);
        $payload['showInputLanguage'] = !empty($settings->showkeyboardlayout);
        $payload['allowQuit'] = !empty($settings->allowuserquitseb);

        $quiturl = trim((string)($settings->linkquitseb ?? ''));
        if ($quiturl !== '') {
            $payload['quitURL'] = $quiturl;
        }

        $payload['quitURLConfirm'] = !empty($settings->userconfirmquit);
        $payload['audioControlEnabled'] = !empty($settings->enableaudiocontrol);
        $payload['audioMute'] = !empty($settings->muteonstartup);
        $payload['browserMediaCaptureCamera'] = !empty($settings->allowcapturecamera);
        $payload['browserMediaCaptureMicrophone'] = !empty($settings->allowcapturemicrophone);
        $payload['allowSpellCheck'] = !empty($settings->allowspellchecking);
        $payload['browserWindowAllowReload'] = !empty($settings->allowreloadinexam);
        $payload['URLFilterEnable'] = true;
        $payload['URLFilterEnableContentFilter'] = !empty($settings->filterembeddedcontent);
        $payload['URLFilterRules'] = self::get_url_filter_rules($settings);
        $payload['startURL'] = self::get_start_url($settings);
        // Must stay false: this reconfiguration must keep the Moodle login obtained in phase 1.
        $payload['examSessionClearCookiesOnStart'] = false;
        $payload['examSessionClearCookiesOnEnd'] = true;
        $payload['removeBrowserProfile'] = true;
        $payload['hashedQuitPassword'] = self::get_quit_password_hash($settings);
        $payload['hashedAdminPassword'] = self::get_password_hash($secret);
        return $payload;
    }

    /**
     * Return VPL start URL used in SEB payload.
     *
     * @param \stdClass $settings SEB record.
     * @return string
     */
    public static function get_start_url(\stdClass $settings): string {
        global $CFG;
        $cmid = settings::get_cmid($settings->vplid);
        return $CFG->wwwroot . '/mod/vpl/forms/edit.php?id=' . $cmid;
    }

    /**
     * Return URL filter rules.
     *
     * @param \stdClass $settings SEB record.
     * @return array
     */
    public static function get_url_filter_rules(\stdClass $settings): array {
        global $CFG;
        $cmid = settings::get_cmid($settings->vplid);
        $base = $CFG->wwwroot . '/mod/vpl/';
        $activityurls = [
            $base . 'view.php?id=' . $cmid,
            $base . 'forms/edit.php?id=' . $cmid,
            $base . 'forms/submissionview.php?id=' . $cmid,
        ];
        $regexallowed = implode("\n", array_map([self::class, 'url_to_regex'], $activityurls));
        if (!empty($settings->activateurlfiltering)) {
            $regexallowed .= "\n" . ($settings->regexallowed ?? '');
            return array_merge(
                self::build_filter_rules(($settings->expressionsallowed ?? ''), false, 1),
                self::build_filter_rules(($settings->expressionsblocked ?? ''), false, 0),
                self::build_filter_rules($regexallowed, true, 1),
                self::build_filter_rules(($settings->regexblocked ?? ''), true, 0)
            );
        } else {
            return self::build_filter_rules($regexallowed, true, 1);
        }
    }

    /**
     * Return an anchored regular expression matching a URL, an optional userid parameter and fragment.
     *
     * Any other extra parameter is rejected, e.g. a repeated ?id=1&id=2 would reach
     * an activity other than the allowed one, as PHP keeps the last occurrence.
     *
     * @param string $url Absolute URL to match.
     * @return string
     */
    public static function url_to_regex(string $url): string {
        return '^' . preg_quote($url) . '(&userid=[0-9]+)?(#.*)?$';
    }

    /**
     * Line-based filter expressions into rules.
     *
     * @param string $expressions Expressions separated by line breaks.
     * @param bool $regex Whether expressions are regular expressions.
     * @param int $action Rule action.
     * @return array<int, array{action:int, active:bool, expression:string, regex:bool}>
     */
    public static function build_filter_rules(string $expressions, bool $regex, int $action): array {
        $result = [];
        foreach (preg_split('/\R+/', trim($expressions)) as $expression) {
            $expression = trim($expression);
            if ($expression === '') {
                continue;
            }
            $result[] = ['action' => $action, 'active' => true, 'expression' => $expression, 'regex' => $regex];
        }
        return $result;
    }
    /**
     * Return hashed password.
     *
     * @param string $password Password to hash.
     * @return string
     */
    public static function get_password_hash(string $password): string {
        $password = trim($password);
        if ($password === '') {
            return '';
        }
        return hash('sha256', $password);
    }

    /**
     * Return hashed quit password.
     *
     * @param \stdClass $settings SEB record.
     * @return string
     */
    public static function get_quit_password_hash(\stdClass $settings): string {
        return self::get_password_hash($settings->quitpassword ?? '');
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
                $normalized[(string)$key] = self::canonicalize_payload($item);
            }
            uksort($normalized, [self::class, 'compare_keys']);

            return $normalized;
        }

        return array_map([self::class, 'canonicalize_payload'], $value);
    }

    /**
     * Compare two payload keys as SEB does: culture invariant, case insensitive ordering.
     *
     * @param string $a First key.
     * @param string $b Second key.
     * @return int
     */
    public static function compare_keys(string $a, string $b): int {
        static $collator = null;
        if ($collator === null) {
            $collator = class_exists('\\Collator') ? new \Collator('root') : false;
        }
        if ($collator !== false) {
            $result = $collator->compare($a, $b);
            if ($result !== false) {
                return $result;
            }
        }
        return strcasecmp($a, $b);
    }

    /**
     * Serialize a payload into the "SEB-JSON" string used to compute the SEB Config Key.
     *
     * See https://safeexambrowser.org/developer/seb-config-key.html
     * No whitespace, no slash/unicode escaping and, notably, literal backslashes
     * (as used in URL filter regular expressions) must not be escaped.
     *
     * @param array $payload SEB configuration payload.
     * @return string
     */
    public static function get_seb_json(array $payload): string {
        $canonpayload = self::substitute_backslashes(self::canonicalize_payload($payload));
        $json = json_encode($canonpayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return str_replace(self::BACKSLASH_SUBSTITUTE, '\\', $json);
    }

    /**
     * Replace literal backslashes in strings by a substitute reverted after JSON encoding.
     *
     * @param mixed $value Value to process.
     * @return mixed
     */
    protected static function substitute_backslashes($value) {
        if (is_string($value)) {
            return str_replace('\\', self::BACKSLASH_SUBSTITUTE, $value);
        }
        if (is_array($value)) {
            return array_map([self::class, 'substitute_backslashes'], $value);
        }
        return $value;
    }

    /**
     * Return true when array has string keys.
     *
     * @param array $value Array to inspect.
     * @return bool
     */
    public static function is_assoc_array(array $value): bool {
        foreach ($value as $key => $item) {
            return is_string($key);
        }
        return false;
    }
}
