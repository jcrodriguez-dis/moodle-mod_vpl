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
 * Module common settings
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 *
 * Define the attributes $settings variable.
 * @var object $settings
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/locallib.php');

$kbyte = 1024;
$megabyte = 1024 * $kbyte;
$minute = 60;
$listmaxfilesize = vpl_get_select_sizes(64 * $kbyte, \mod_vpl\util\phpconfig::get_post_max_size());
$listmaxtime = vpl_get_select_time(120 * $minute);
$listmaxexefilesize = vpl_get_select_sizes(16 * $megabyte);
$listmaxexememory = vpl_get_select_sizes(16 * $megabyte);
$defaultmaxfilesize = vpl_get_array_key($listmaxfilesize, $megabyte);
$defaultmaxtime = vpl_get_array_key($listmaxtime, 16 * $minute);
$defaultmaxexefilesize = vpl_get_array_key($listmaxexefilesize, 128 * $megabyte);
$defaultmaxexememory = vpl_get_array_key($listmaxexememory, 1024 * $megabyte);
$defaultmaxexeprocesses = 600;
$defaultdefaultfilesize = vpl_get_array_key($listmaxfilesize, 64 * $kbyte);
$defaultdefaulttime = vpl_get_array_key($listmaxtime, 4 * $minute);
$defaultdefaultexefilesize = vpl_get_array_key($listmaxexefilesize, 64 * $megabyte);
$defaultdefaultexememory = vpl_get_array_key($listmaxexememory, 128 * $megabyte);
$defaultdefaultexeprocesses = 200;
$prefix = 'mod_vpl/';
$settings->add(new admin_setting_heading('heading1', '', get_string('maxresourcelimits', COMPVPL)));
$settings->add(
    new admin_setting_configselect(
        $prefix . 'maxfilesize',
        get_string('maxfilesize', COMPVPL),
        get_string('maxfilesize', COMPVPL),
        $defaultmaxfilesize,
        $listmaxfilesize
    )
);
$settings->add(
    new admin_setting_configselect(
        $prefix . 'maxexetime',
        get_string('maxexetime', COMPVPL),
        get_string('maxexetime', COMPVPL),
        $defaultmaxtime,
        $listmaxtime
    )
);
$settings->add(
    new admin_setting_configselect(
        $prefix . 'maxexefilesize',
        get_string('maxexefilesize', COMPVPL),
        get_string('maxexefilesize', COMPVPL),
        $defaultmaxexefilesize,
        $listmaxexefilesize
    )
);
$settings->add(
    new admin_setting_configselect(
        $prefix . 'maxexememory',
        get_string('maxexememory', COMPVPL),
        get_string('maxexememory', COMPVPL),
        $defaultmaxexememory,
        $listmaxexememory
    )
);
$settings->add(
    new admin_setting_configtext(
        $prefix . 'maxexeprocesses',
        get_string('maxexeprocesses', COMPVPL),
        get_string('maxexeprocesses', COMPVPL),
        $defaultmaxexeprocesses,
        PARAM_INT,
        4
    )
);
$settings->add(new admin_setting_heading('headingd', '', get_string('defaultresourcelimits', COMPVPL)));
$name = 'defaultfilesize';
$settings->add(
    new admin_setting_configselect(
        $prefix . $name,
        get_string($name, COMPVPL),
        get_string($name, COMPVPL),
        $defaultdefaultfilesize,
        $listmaxfilesize
    )
);
$name = 'defaultexetime';
$settings->add(
    new admin_setting_configselect(
        $prefix . $name,
        get_string($name, COMPVPL),
        get_string($name, COMPVPL),
        $defaultdefaulttime,
        $listmaxtime
    )
);
$name = 'defaultexefilesize';
$settings->add(
    new admin_setting_configselect(
        $prefix . $name,
        get_string($name, COMPVPL),
        get_string($name, COMPVPL),
        $defaultdefaultexefilesize,
        $listmaxexefilesize
    )
);
$name = 'defaultexememory';
$settings->add(
    new admin_setting_configselect(
        $prefix . $name,
        get_string($name, COMPVPL),
        get_string($name, COMPVPL),
        $defaultdefaultexememory,
        $listmaxexememory
    )
);
$name = 'defaultexeprocesses';
$settings->add(
    new admin_setting_configtext(
        $prefix . $name,
        get_string($name, COMPVPL),
        get_string($name, COMPVPL),
        $defaultdefaultexeprocesses,
        PARAM_INT,
        4
    )
);
$settings->add(new admin_setting_heading('heading2', '', get_string('jail_servers_config', COMPVPL)));
$default = "# This server is only for test use.\n";
$default .= "# Install your own Jail server and remove the following line as soon as possible.\n";
$default .= 'https://demojail.dis.ulpgc.es';
$settings->add(
    new admin_setting_configtextarea(
        $prefix . 'jail_servers',
        get_string('jail_servers', COMPVPL),
        get_string('jail_servers_description', COMPVPL),
        $default
    )
);
$settings->add(
    new admin_setting_configcheckbox(
        $prefix . 'use_xmlrpc',
        get_string('use_xmlrpc', COMPVPL),
        get_string('use_xmlrpc_description', COMPVPL),
        0
    )
);
$settings->add(
    new admin_setting_configcheckbox(
        $prefix . 'acceptcertificates',
        get_string('acceptcertificates', COMPVPL),
        get_string('acceptcertificates_description', COMPVPL),
        0
    )
);
$wsoptions = [
        'always_use_wss' => get_string('always_use_wss', COMPVPL),
        'always_use_ws' => get_string('always_use_ws', COMPVPL),
        'depends_on_https' => get_string('depends_on_https', COMPVPL),
];
$name = 'websocket_protocol';
$settings->add(
    new admin_setting_configselect(
        $prefix . 'websocket_protocol',
        get_string('websocket_protocol', COMPVPL),
        get_string('websocket_protocol_description', COMPVPL),
        'depends_on_https',
        $wsoptions
    )
);
$name = 'proxy';
$settings->add(
    new admin_setting_configtext(
        $prefix . $name,
        get_string($name, COMPVPL),
        get_string($name . '_description', COMPVPL),
        '',
        PARAM_URL
    )
);
$settings->add(new admin_setting_heading('heading3', '', get_string('miscellaneous')));
$defaultsubmitmethods = [
    'auto' => get_string('submitmethod_default_auto', COMPVPL),
    'files' => get_string('submitfiles', COMPVPL),
    'archive' => get_string('archive', COMPVPL),
];
$settings->add(
    new admin_setting_configselect(
        $prefix . 'defaultsubmitmethod',
        get_string('submitmethod_default', COMPVPL),
        get_string('submitmethod_default_description', COMPVPL),
        'auto',
        $defaultsubmitmethods
    )
);
$settings->add(
    new admin_setting_configcheckbox(
        $prefix . 'use_watermarks',
        get_string('usewatermarks', COMPVPL),
        get_string('usewatermarks_description', COMPVPL),
        0
    )
);

$list = vpl_get_select_time();
$default = vpl_get_array_key($list, 60);
$settings->add(
    new admin_setting_configselect(
        $prefix . 'discard_submission_period',
        get_string('discard_submission_period', COMPVPL),
        get_string('discard_submission_period_description', COMPVPL),
        $default,
        $list
    )
);
$list = [
    0 => get_string('backupallsubmissions_no', COMPVPL),
    1 => get_string('backupallsubmissions_user_choice', COMPVPL),
    2 => get_string('backupallsubmissions_yes', COMPVPL),
];
$settings->add(
    new admin_setting_configselect(
        $prefix . 'backupallsubmissions',
        get_string('backupallsubmissions', COMPVPL),
        get_string('backupallsubmissions_description', COMPVPL),
        0,
        $list
    )
);
$list = [
        'ambiance',
        'chaos',
        'chrome',
        'clouds_midnight',
        'clouds',
        'cobalt',
        'crmson_editor',
        'dawn',
        'dreamweaver',
        'eclipse',
        'github',
        'idle_fingers',
        'iplastic',
        'katzenmilch',
        'kr_theme',
        'kr',
        'kuroir',
        'merbivore_soft',
        'merbivore',
        'mono_industrial',
        'monokai',
        'pastel_on_dark',
        'solarized_dark',
        'solarized_light',
        'sqlserver',
        'terminal',
        'texmate',
        'tomorrow_night_blue',
        'tomorrow_night_bright',
        'tomorrow_night_eighties',
        'tomorrow_night',
        'tomorrow',
        'twilight',
        'vibrant_ink',
        'xcode',
];
$themelist = [];
foreach ($list as $theme) {
    $themelist[$theme] = $theme;
}
$settings->add(
    new admin_setting_configselect(
        $prefix . 'editor_theme',
        get_string('editortheme', COMPVPL),
        get_string('editortheme', COMPVPL),
        'chrome',
        $themelist
    )
);
