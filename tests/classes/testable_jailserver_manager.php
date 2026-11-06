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
 * Injectable jail transport for tests.
 *
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodriguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl\tests;

/**
 * Replace only the network client while retaining the production setup.
 */
class testable_jailserver_manager extends \vpl_jailserver_manager {
    /** @var \curl|null Transport supplied by the test. */
    public static $client = null;

    /**
     * Configure the real client to reset state, then return the injected transport.
     *
     * @param string $server Jail URL.
     * @param string $request Request body.
     * @param bool $fresh Whether a fresh connection is required.
     * @return \curl Injected client.
     */
    public static function get_curl($server, $request, $fresh = false) {
        parent::get_curl($server, $request, $fresh);
        if (self::$client === null) {
            throw new \coding_exception('No jail transport supplied by the test');
        }
        return self::$client;
    }
}
