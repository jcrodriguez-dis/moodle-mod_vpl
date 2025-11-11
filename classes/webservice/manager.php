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
 * Webservice management functions.
 * @package mod_vpl
 * @copyright 2022 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl\webservice;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/lib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');
use webservice, moodle_url, html_writer;

/**
 * Webservice management functions.
 * @copyright 2022 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * @var int LOCAL scope for the webservice.
     */
    public const LOCAL = 0;

    /**
     * @var int GLOBAL scope for the webservice.
     */
    public const GLOBAL = 1;

    /**
     * @var \mod_vpl The VPL instance this webservice is associated with.
     */
    protected $vpl;

    /**
     * @var ?object The VPL webservice record.
     */
    protected static $service = null;

    /**
     * Constructor for the webservice manager.
     * @param \mod_vpl $vpl The VPL instance this webservice is associated with.
     */
    public function __construct($vpl) {
        $this->vpl = $vpl;
    }

    /**
     * Get the VPL webservice DB record.
     * This is a singleton method to retrieve the VPL webservice.
     *
     * @return object The VPL webservice record.
     */
    protected static function get_service() {
        global $DB;
        if (self::$service === null) {
            self::$service = $DB->get_record('external_services', [ 'shortname' => 'mod_vpl_edit', 'enabled' => 1 ]);
        }
        return self::$service;
    }

    /**
     * Check if the webservice is available.
     * This checks if webservices are enabled and if the VPL service exists.
     *
     * @return bool True if the service is available, false otherwise.
     */
    public static function service_is_available() {
        global $CFG;
        return $CFG->enablewebservices && !empty(self::get_service());
    }

    /**
     * Retrieve or generate a global webservice token for current user.
     * @return string The token, or an empty string if service is unavailable.
     */
    public static function get_global_token() {
        global $DB, $USER;
        $now = time();
        if (is_siteadmin()) {
            // Never display site admin global tokens.
            return '';
        }
        if (!self::service_is_available()) {
            return '';
        }
        $tokenrecord = $DB->get_record('external_tokens', [
                'userid' => $USER->id,
                'externalserviceid' => self::get_service()->id,
                'tokentype' => EXTERNAL_TOKEN_PERMANENT,
        ]);
        if (! empty($tokenrecord) && $tokenrecord->validuntil > 0 && $tokenrecord->validuntil < $now) {
            unset($tokenrecord); // Will be deleted before creating a new one.
        }
        if (empty($tokenrecord)) {
            $webservice = new webservice();
            $webservice->generate_user_ws_tokens($USER->id);
            $tokenrecord = $DB->get_record('external_tokens', [
                    'userid' => $USER->id,
                    'externalserviceid' => self::get_service()->id,
                    'tokentype' => EXTERNAL_TOKEN_PERMANENT,
            ]);
            if (empty($tokenrecord)) {
                return '';
            }
        }
        return $tokenrecord->token;
    }

    /**
     * Retrieve or generate a temporary embedded webservice token for this vpl and given user.
     * @param int|null $userid The id of the user to get the token for, null means current user.
     * @return string The token, or an empty string if service is unavailable.
     */
    public function get_temporary_embedded_token($userid = null) {
        global $DB, $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        if (!self::service_is_available()) {
            return '';
        }
        // If such a token already exists and is still valid, use it.
        $contextid = $this->vpl->get_context()->id;
        $tokens = $DB->get_records('external_tokens', [
                'tokentype' => EXTERNAL_TOKEN_EMBEDDED,
                'userid' => $userid,
                'externalserviceid' => self::get_service()->id,
                'sid' => session_id(),
                'contextid' => $contextid,
        ]);
        foreach ($tokens as $token) {
            if ($token->validuntil == 0 || $token->validuntil > time()) {
                return $token->token;
            }
        }
        // No valid token found, generate a new one.
        return external_generate_token(EXTERNAL_TOKEN_EMBEDDED, self::get_service(), $userid, $contextid, time() + DAYSECS);
    }

    /**
     * Print the webservice information for the current VPL instance.
     *
     * @param int $scope The scope of the webservice, either LOCAL or GLOBAL.
     */
    public function print_webservice($scope) {
        switch ($scope) {
            case self::LOCAL:
                $token = $this->get_temporary_embedded_token();
                $baseurl = '/mod/vpl/webservice.php';
                break;
            case self::GLOBAL:
                $token = self::get_global_token();
                $baseurl = '/webservice/rest/server.php';
                break;
            default:
                $token = '';
        }
        if ($token) {
            // Personal token info.
            vpl_print_copyable_info(get_string('webservicetoken', VPL), self::spoiler($token), $token);

            // Webservice URL info.
            $url = new moodle_url($baseurl);
            vpl_print_copyable_info(get_string('webserviceurl', VPL), $url);

            $id = $this->vpl->get_course_module()->id;
            $fullurl = "$url?moodlewsrestformat=json&id=$id&wstoken=$token&wsfunction=";
            // Visually encrypt token. This is not meant to be secure, only hide e.g. for screen sharing.
            $urldisplay = str_replace($token, self::visual_encrypt($token), s($fullurl)); // Token is unique in URL string.
            vpl_print_copyable_info(get_string('webserviceurlfull', VPL), $urldisplay, $fullurl);

            if (!empty($this->vpl->get_instance()->password)) {
                // Display a notice informing that password should be added to URL to use webservice.
                echo html_writer::div(vpl_info_icon() . get_string('webserviceurlpwdnotice', VPL));
            }
        } else {
            echo html_writer::div(get_string('notavailable'));
        }
    }

    /**
     * Create a spoiler div with a clickable text to show the content.
     *
     * @param string $text The text to hide initially.
     * @return string HTML div with the text and a clickable hider.
     */
    protected static function spoiler($text) {
        $hider = html_writer::div(get_string('clicktoshow', VPL), 'hider', [ 'onclick' => 'this.remove()' ]);
        return html_writer::div($text . $hider, 'spoiler');
    }

    /**
     * Visual encryption of a text, to be used in webservice URLs.
     * This is not meant to be secure, only to hide the text from casual view.
     *
     * @param string $text The text to encrypt visually.
     * @param int|null $length Optional length of the visual encryption, defaults to the length of the text.
     * @return string HTML span with the encrypted text and cryptchar spans.
     */
    protected static function visual_encrypt($text, $length = null) {
        return html_writer::span(s($text), 'crypted') . str_repeat(html_writer::span('', 'cryptchar'), $length ?? strlen($text));
    }
}
