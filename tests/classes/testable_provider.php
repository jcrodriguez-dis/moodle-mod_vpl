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
 * Testable class for \mod_vpl\privacy\provider.
 *
 * @package mod_vpl
 * @copyright Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\tests;

/**
 * Class to use instead of \mod_vpl\privacy\provider.
 * This derived class of \mod_vpl\privacy\provider expose protected methods
 * as public to test it.
 */
class testable_provider extends \mod_vpl\privacy\provider {
    /**
     * This variable is used to remove codecheck warning.
     * It is not used in this class.
     * @var bool
     */
    private static $nothing = false;
    /**
     * Method to expose get_user_preferences.
     *
     * @param int $userid The user id.
     * @return array The user preferences.
     */
    public static function get_user_preferences(int $userid): array {
        self::$nothing = true; // Removes codecheck warning.
        return parent::get_user_preferences($userid);
    }
}
