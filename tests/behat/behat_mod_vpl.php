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
require_once(__DIR__ . '/../../../../lib/behat/core_behat_file_helper.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * VPL activity definitions.
 *
 * @package   mod_vpl
 * @category  test
 * @copyright 2021 Juan Carlos Rodríguez-del-Pino
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_vpl extends behat_base {
    use core_behat_file_helper;

    /**
     * Upload a fixture to a VPL filepicker, including pickers without labels.
     *
     * @When /^I upload the VPL fixture "([^"]*)" to the file field "([^"]*)"$/
     * @param string $filename Fixture filename.
     * @param string $field Filepicker field name.
     */
    public function i_upload_the_vpl_fixture_to_the_file_field(string $filename, string $field): void {
        $this->require_javascript();
        if (!$this->has_tag('_file_upload')) {
            throw new ExpectationException('VPL file uploads require the @_file_upload tag.', $this->getSession());
        }
        $path = __DIR__ . '/datafiles/' . $filename;
        if (!is_readable($path)) {
            throw new ExpectationException("The VPL fixture '$filename' cannot be read.", $this->getSession());
        }
        $picker = $this->find(
            'xpath',
            '//input[@name=' . behat_context_helper::escape($field) . ']' .
                '/ancestor::*[@data-fieldtype="filepicker"]'
        );
        $this->open_add_file_window($picker, get_string('pluginname', 'repository_upload'));
        $this->find_file('repo_upload_file')->attachFile($path);
        $this->find_button(get_string('upload', 'repository'))->press();
        $this->wait_for_pending_js();
    }

    /**
     * Check that the restricted editor cancels an external clipboard paste.
     *
     * @Then /^pasting "([^"]*)" into the VPL editor should be blocked$/
     * @param string $contents External clipboard contents.
     */
    public function pasting_into_the_vpl_editor_should_be_blocked(string $contents): void {
        $this->require_javascript();
        $this->find('css', '#vpl_tabs .ace_text-input');
        $this->wait_for_vpl_editor('true', 'The VPL editor did not finish loading before the paste.');
        $encodedcontents = json_encode($contents, JSON_THROW_ON_ERROR);
        $blocked = $this->getSession()->evaluateScript("(function() {
            var target = document.querySelector('#vpl_tabs .ace_text-input');
            var clipboard = new DataTransfer();
            clipboard.setData('text/plain', $encodedcontents);
            var event = new ClipboardEvent('paste', {
                bubbles: true,
                cancelable: true,
                clipboardData: clipboard
            });
            return !target.dispatchEvent(event) && event.defaultPrevented;
        })()");
        if (!$blocked) {
            throw new ExpectationException('The VPL editor did not block the external paste.', $this->getSession());
        }
    }

    /**
     * Wait for an editor condition and any VPL file transfer to finish.
     *
     * @param string $condition JavaScript expression to check.
     * @param string $message Failure diagnostic.
     * @throws ExpectationException If the editor does not become ready.
     */
    protected function wait_for_vpl_editor(string $condition, string $message): void {
        $session = $this->getSession();
        $ready = $session->wait(
            self::get_extended_timeout() * 1000,
            "document.querySelector('.vpl_ide_progressbar') === null && ($condition)"
        );
        if (!$ready) {
            throw new ExpectationException($message, $session);
        }
    }

    /**
     * Wait for the JavaScript suites and include their diagnostics in Behat failures.
     *
     * @Then /^the VPL JavaScript tests should pass$/
     * @throws ExpectationException If tests fail, do not start, or do not finish.
     */
    public function the_vpl_javascript_tests_should_pass(): void {
        $session = $this->getSession();
        $finished = $session->wait(
            self::get_extended_timeout() * 1000,
            "(function() {
                var report = document.getElementById('test_results');
                return report && /^(passed|failed)$/.test(report.getAttribute('data-status'));
            })()"
        );
        $report = $session->getPage()->find('css', '#test_results');
        if ($report === null) {
            throw new ExpectationException('The VPL JavaScript test report (#test_results) was not found.', $session);
        }
        $status = $report->getAttribute('data-status');
        $details = $report->getText();
        if (!$finished || !in_array($status, ['passed', 'failed'], true)) {
            throw new ExpectationException(
                "VPL JavaScript tests did not finish (status: $status).\n$details",
                $session
            );
        }
        if ($status !== 'passed') {
            throw new ExpectationException("VPL JavaScript tests failed.\n$details", $session);
        }
    }

    /**
     * Click on any element
     *
     * @Given /^I click on "([^"]*)" in VPL$/
     * @param string $selector
     * @return void
     */
    public function i_click_on_selector_in_vpl($selector) {
        $this->find('css', $selector);
        $encodedselector = json_encode($selector, JSON_THROW_ON_ERROR);
        $this->wait_for_vpl_editor(
            "(function() {
                var element = document.querySelector($encodedselector);
                return element && !element.disabled && !element.classList.contains('ui-state-disabled');
            })()",
            "The VPL element '$selector' did not become enabled."
        );
        $this->getSession()->evaluateScript("document.querySelector($encodedselector).click();");
        if ($selector === '#vpl_ide_save') {
            $this->wait_for_vpl_editor(
                "document.querySelector('#vpl_ide_save').classList.contains('ui-state-disabled')",
                'The VPL editor did not finish saving the files.'
            );
        }
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
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $binarytypes = ['application/zip' => 'zip',
                            'application/pdf' => 'pdf',
                            'image/png' => 'png',
                            'image/jpeg' => 'jpg',
                         ];
        $type = array_search($ext, $binarytypes);
        $script = "(function() {";
        if ($type === false) {
            $type = 'text/plain';
            $contentsesc = json_encode($contents, JSON_THROW_ON_ERROR);
            $script .= "var filedata = $contentsesc;";
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
        $filenameesc = json_encode($filename, JSON_THROW_ON_ERROR);
        $script .= "
                $target = new File([filedata], $filenameesc, {type: '$type', lastModified: Date.now()});
            })()";
        return $script;
    }
    /**
     * Drop a file that content text
     *
     * @Given /^I drop the file "([^"]*)" containing "((?:[^"]|\\.)*)" on "([^"]*)" in VPL$/
     *
     * @param string $filename Name of the file to drop
     * @param string $contents Contents of the file to drop, may be binary (zip, pdf, jpg, png)
     * @param string $selector CSS selector as drop target
     * @return void
     */
    public function i_drop_the_file_containing_on_in_vpl($filename, $contents, $selector) {
        $this->find('css', $selector);
        $this->wait_for_vpl_editor('true', 'The VPL editor did not finish loading before the file drop.');
        // Testing framework does not accept heredoc syntax.
        $contents = str_replace("\\n", "\n", $contents);
        $scriptfile = $this->generate_drop_file($filename, $contents, 'file');
        $encodedselector = json_encode($selector, JSON_THROW_ON_ERROR);
        $script = "(function() {
            var file;
            $scriptfile;
            var datat = new DataTransfer();
            datat.items.add(file);
            var dropEvent = new Event('drop',
                {
                    bubbles: true,
                    cancelable: true
                });
            dropEvent.dataTransfer = datat;
            var element = document.querySelector($encodedselector);
            element.dispatchEvent(dropEvent);
        })()";
        $this->getSession()->evaluateScript($script);
        $this->wait_for_vpl_editor('true', "The VPL editor did not finish importing '$filename'.");
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
        $this->find('css', $selector);
        $this->wait_for_vpl_editor('true', 'The VPL editor did not finish loading before the file drop.');
        $script = "(function() {
            var file;
            var filelist = [];";
        $files = preg_split("/[|]/", $filenames);
        foreach ($files as $filename) {
            if ($filename == '') {
                throw new ExpectationException(
                    'Bad format for file names "' . $filenames . '"',
                    $this->getSession()
                );
            }
            $contents = file_get_contents(__DIR__ . "/datafiles/" . $filename);
            if ($contents === false) {
                throw new ExpectationException(
                    'The file "' . $filename . '" cannot be read',
                    $this->getSession()
                );
            }
            $scriptfile = $this->generate_drop_file($filename, $contents, 'file');
            $script .= "$scriptfile; filelist.push(file);";
        }
        $script .= "
            var datat = new DataTransfer();
            for (var i = 0; i < filelist.length; i++) {
                datat.items.add(filelist[i]);
            }
        ";
        $encodedselector = json_encode($selector, JSON_THROW_ON_ERROR);
        $script .= "var dropEvent = new Event('drop',
                {
                    bubbles: true,
                    cancelable: true
                });
            dropEvent.dataTransfer = datat;
            var element = document.querySelector($encodedselector);
            element.dispatchEvent(dropEvent);
        })()";
        $this->getSession()->evaluateScript($script);
        $this->wait_for_vpl_editor('true', "The VPL editor did not finish importing '$filenames'.");
    }
}
