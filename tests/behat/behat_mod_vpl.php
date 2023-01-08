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
        sleep(1);
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
     * Generate JavaScript code to prepare file to drop
     *
     * @param string $filename File name
     * @param string $contents File contents, may be binary (zip, pdf, jpg, png)
     * @param string $target JavaScript to use as return value
     * @return string
     */
    protected function generate_drop_file($filename, $contents, $target) {
        $ext = pathinfo( $filename, PATHINFO_EXTENSION );
        $binarytypes = array('application/zip' => 'zip',
                            'application/pdf' => 'pdf',
                            'image/png' => 'png',
                            'image/jpg' => 'jpg'
                         );
        $type = array_search($ext, $binarytypes);
        $script = "(function() {";
        if ( $type === false ) {
            $type = '';
            $contentsesc = addcslashes($contents, "\\\"'\r\n\t\f");
            // Testing framework does not accept heredoc syntax.
            $script .= "var filedata = \"$contentsesc\";";
        } else {
            $contentb64 = base64_encode($contents);
            $script .= "
                var StringOfBytes = atob('$contentb64');
                var bytes = new Array(StringOfBytes.length);
                for (var i = 0; i < StringOfBytes.length; i++) {
                    bytes[i] = StringOfBytes.charCodeAt(i);
                }
                var filedata = new Uint8Array(bytes);";
        }
        $script .= "
                var localfile = new Blob([filedata], {type: '$type'});
                localfile.name = '$filename';
                localfile.lastModifiedDate = new Date();
                $target = localfile;
            })()";
        return $script;
    }
    /**
     * Drop a file that content text
     *
     * @Given /^I drop the file "([^"]*)" contening "((?:[^"]|\\.)*)" on "([^"]*)" in VPL$/
     * @return void
     */
    public function i_drop_the_file_contening_on_in_vpl($filename, $contents, $selector) {
        // Testing framework does not accept heredoc syntax.
        $scriptfile = $this->generate_drop_file($filename, $contents, 'file');
        $script = "(function() {
            var file;
            $scriptfile;
            var fileList = [file];
            var drop = $.Event({type: 'drop', dataTransfer: {files: fileList}});
            drop.isSimulated = true;
            $('$selector').trigger(drop);
        })()";
        $this->getSession()->evaluateScript($script);
        sleep(1);
    }

    /**
     * Drop files from datafile subdirectory
     *
     * @Given /^I drop the files? "([^"]*)" on "([^"]*)" in VPL$/
     *
     * @param string $filenames Files to drop separate by '|`' g.e. a.c|b.c
     * @param string $selector CSS selector as drop target
     * @return void
     */
    public function i_drop_the_file_on_in_vpl($filenames, $selector) {
        $script = "(function() {
            var fileList = [];
            var file;";
        $files = preg_split("/[|]/", $filenames);
        foreach ($files as $filename) {
            if ($filename == '') {
                throw new ExpectationException('Bad format for file names "' . $filenames. '"',
                    $this->getSession());
            }
            $contents = file_get_contents(__DIR__ ."/datafiles/" . $filename);
            if ($contents === false) {
                throw new ExpectationException('The file "' . $filename. '" cannot be read',
                    $this->getSession());
            }
            $scriptfile = $this->generate_drop_file($filename, $contents, 'file');
            $script .= "$scriptfile; fileList.push(file);";
        }
        $script .= "var drop = $.Event({type: 'drop', dataTransfer: {files: fileList}});
                drop.isSimulated = true;
                $('$selector').trigger(drop);
        })()";
        $this->getSession()->evaluateScript($script);
        sleep(count($files));
    }
}
