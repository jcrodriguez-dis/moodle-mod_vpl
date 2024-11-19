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
 * A schedule task for VPL cron.
 *
 * @package mod_vpl
 * @copyright 2020 onwards Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

namespace mod_vpl\task;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/message/lib.php');

/**
 * Class check_vpljs_task to be used by the task system.
 * The task check for outdated VPL Jail Server reporting to the site administrator.
 *
 */
class check_vpljs_task extends \core\task\scheduled_task {
    const VPL = 'vpl';
    const VPL_JAILSERVERS = 'vpl_jailservers';

    /**
     * Get a descriptive name for this task shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask_check_vpljs', 'mod_vpl');
    }

    /**
     * Get bad jail server detected and reset status
     *
     * @return object[]
     */
    public function get_bad_jail_servers() {
        global $DB;
        $select = 'nbusy < 0';
        $servers = $DB->get_records_select(self::VPL_JAILSERVERS, $select);
        foreach ($servers as $server) {
            $record = new stdClass();
            $record->id = $server->id;
            $record->nbusy = 0;
            $DB->update_record(self::VPL_JAILSERVERS, $record);
        }
        return $servers;
    }

    /**
     * Run check VPL Jail Servers status.
     */
    public function execute() {
        global $CFG;
        $servers = self::get_bad_jail_servers();
        if ($servers) {
            $adminuser = get_admin();
            $noreplyuser = \core_user::get_noreply_user();
            if ($adminuser) {
                $site = get_site();
                $header = get_string('message::subject_bad_jailservers', self::VPL, $CFG->wwwroot);
                $subject = "{$site->shortname}: $header";
                $body = "# $header\n\n";
                $body .= get_string('message::body_header_bad_jailservers', self::VPL);
                $body .= "\n\n";
                foreach ($servers as $server) {
                    $parsed = parse_url($server->server);
                    $name = $parsed['host'];
                    $body .= "- {$name}\n";
                }
                $body .= "\n" . get_string('message::body_footer_bad_jailservers', self::VPL);
                echo $body;
                $message = new \core\message\message();
                $message->component = 'mod_vpl';
                $message->name = 'bad_jailservers';
                $message->userfrom = $adminuser;
                $message->userto = $adminuser;
                $message->subject = $subject;
                $message->fullmessage = $body;
                $message->fullmessageformat = FORMAT_MARKDOWN;
                $message->fullmessagehtml = format_text($body, FORMAT_MARKDOWN);
                // Send the message.
                $success = message_send($message) !== false;
                if (! $success) { // Try send by direct email.
                    $success = email_to_user($adminuser, $noreplyuser, $subject, htmlspecialchars($body));
                }
                return $success;
            } else {
                return false;
            }
        }
        return true;
    }
}
