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

defined('MOODLE_INTERNAL') || die();

/**
 * Coverage information for the mod_vpl component.
 *
 * @package    mod_vpl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
return new class extends phpunit_coverage_info {
    /**
     * @var array The list of folders relative to the plugin root to include in coverage generation
     */
    protected $includelistfolders = [
        'classes',
        'similarity',
        'forms',
        'jail',
        'views'
    ];

    /**
     * @var array The list of files relative to the plugin root to include in coverage generation
     */
    protected $includelistfiles = [

    ];

    /**
     * @var array The list of folders relative to the plugin root to exclude from coverage generation.
     *
     * It is neccesary to set @codeCoverageIgnore in order to truly
     * exclude this folders for code coverage
     */
    protected $excludelistfolders = [
        'tests'
    ];

    /**
     * @var array The list of files relative to the plugin root to exclude from coverage generation.
     *
     * It is neccesary to set @codeCoverageIgnore in order to truly
     * exclude this files for code coverage
     */
    protected $excludelistfiles = [
        'externallib.php',
        'lib.php',
        'locallib.php',
        'classes/token.php',
        'classes/token_type.php',
        'classes/assertf.php'
    ];
};
