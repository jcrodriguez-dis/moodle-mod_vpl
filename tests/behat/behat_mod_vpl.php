<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions for VPL activity.
 *
 * @package   mod_vpl
 * @category  test
 * @copyright 2021 Juan Carlos Rodríguez-del-Pino
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * VPL activity definitions.
 *
 * @package   mod_vpl
 * @category  test
 * @copyright 2021 Juan Carlos Rodríguez-del-Pino
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_vpl extends behat_base {

    /**
     * Click on any element
     *
     * @Given /^I click on "([^"]*)" in VPL$/
     * @param string $selector
     * @return void
     */
    public function i_click_on_selector_in_vpl($selector) {
        $script = "$(\"$selector\")[0].click();";
        $this->getSession()->evaluateScript($script);
    }

    /**
     * Accept confirm popup
     *
     * @Given /^I accept confirm in VPL$/
     * @return void
     */
    public function i_accept_confirm_in_vpl() {
        $script = "window.confirm = function(){return true;};";
        $this->getSession()->evaluateScript($script);
    }

    /**
     * Drop file with text content
     *
     * @Given /^I drop the file "([^"]*)" contening "([^"]*)" on "([^"]*)" in VPL$/
     * @return void
     */
    public function i_drop_the_file_contening_on_in_vpl($finename, $contents, $selector) {
        $contentesc = addslashes_js($contents);
        // Testing framework does not accept heredoc syntax.
        $script = "(function() {
            file = new Blob([\"$contentesc\"], {type: \"\"});
            file.name = \"$finename\";
            file.lastModifiedDate = new Date();
            fileList = [file];
            drop = $.Event({type: \"drop\", dataTransfer: {files: fileList}});
            $(\"$selector\").trigger(drop);
        })()";
        $this->getSession()->evaluateScript($script);
    }
}
