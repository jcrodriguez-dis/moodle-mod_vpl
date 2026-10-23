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
 * Unit tests for the mod_vpl\util\file_icon utility class.
 * @package mod_vpl
 * @copyright 2026 Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vpl;

use mod_vpl\util\file_icon;

/**
 * Unit tests for \mod_vpl\util\file_icon.
 *
 * @group mod_vpl
 * @group mod_vpl_util
 * @covers \mod_vpl\util\file_icon
 */
final class util_file_icon_test extends \advanced_testcase {
    /**
     * Check special files and language extensions select their own icon.
     */
    public function test_get_icon_name_for_known_file_types(): void {
        $cases = [
            'src/Makefile' => 'make',
            'styles/main.css' => 'css',
            'photo.PNG' => 'image',
            'recording.mp3' => 'audio',
            'movie.webm' => 'video',
            'archive.zip' => 'binary',
            'workspace.blockly2' => 'blockly',
            'source.py' => 'python',
            'config.json' => 'json',
            'document.xml' => 'xml',
        ];

        foreach ($cases as $filename => $expected) {
            $this->assertSame($expected, file_icon::get_name($filename), $filename);
        }
    }

    /**
     * Check optional data distinguishes unknown binary files from text files.
     */
    public function test_get_icon_name_uses_optional_data(): void {
        $this->assertSame('binary', file_icon::get_name('unknown.dat'));
        $this->assertSame('binary', file_icon::get_name('unknown.unknown', "\0\1"));
        $this->assertSame('text', file_icon::get_name('unknown.unknown', 'plain text'));
        $this->assertSame('text', file_icon::get_name('unknown.unknown'));
    }

    /**
     * Check only the designated VPL scripts use the execution icon.
     */
    public function test_vpl_execution_script_icons(): void {
        $scripts = [
            'vpl_run.sh',
            'vpl_debug.sh',
            'vpl_evaluate.sh',
            'vpl_test_evaluate.sh',
            'pre_vpl_run.sh',
        ];
        foreach ($scripts as $filename) {
            $this->assertSame('vplscript', file_icon::get_name($filename), $filename);
            $this->assertSame('vplscript', file_icon::get_name('execution/' . $filename), $filename);
            $this->assertSame('binary', file_icon::get_name($filename, "\0\1"), $filename);
        }
        foreach (['script.sh', 'vpl_helper.sh', 'my_vpl_run.sh', 'VPL_RUN.sh', 'vpl_run.sh.bak'] as $filename) {
            $this->assertNotSame('vplscript', file_icon::get_name($filename), $filename);
        }
        $this->assertSame('shell', file_icon::get_name('script.sh'));
        $this->assertSame('cases', file_icon::get_name('vpl_evaluate.cases'));
        $url = file_icon::get_url(file_icon::get_name('vpl_run.sh'));
        $this->assertSame('/mod/vpl/pix/fileicons/vplscript.svg', $url->out_as_local_url(false));
        $this->assertFileExists(__DIR__ . '/../pix/fileicons/vplscript.svg');
    }

    /**
     * Check icon URL points to the selected SVG in the plugin pix directory.
     */
    public function test_get_icon_url(): void {
        $url = file_icon::get_url(file_icon::get_name('source.py'));

        $this->assertInstanceOf(\moodle_url::class, $url);
        $this->assertSame('/mod/vpl/pix/fileicons/python.svg', $url->out_as_local_url(false));
    }
}
