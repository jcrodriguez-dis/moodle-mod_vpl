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

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/vpl.class.php');

require_login();
$id = required_param('id', PARAM_INT);
$token = optional_param('token', '', PARAM_ALPHANUMEXT);

$vpl = new mod_vpl($id);
$vpl->prepare_page('seb.php', ['id' => $id]);
$vpl->require_capability(VPL_VIEW_CAPABILITY);

if (!$vpl->is_visible()) {
    vpl_redirect('?id=' . $id, get_string('notavailable'));
    die;
}

$record = \mod_vpl\seb\settings::get_effective_record($vpl->get_instance());
if (empty($record->requiresafeexambrowser)) {
    vpl_redirect('?id=' . $id, get_string('notavailable'));
    die;
}

$starturl = new moodle_url('/mod/vpl/view.php', ['id' => $id]);

if (!empty($record->enablesebsession)) {
    
    global $USER;

    $session = null;
    
    if ($token !== '') {
        
        $session = \mod_vpl\seb\session_manager::get_by_public_token($token);
        
        if (!$session || (int)$session->vplid !== (int)$record->vplid || (int)$session->userid !== (int)$USER->id) {
            vpl_redirect('?id=' . $id, get_string('notavailable'));
            die;
        }

        $session = \mod_vpl\seb\session_manager::refresh_phase1($record, $session, $starturl);
        
        } else {
            $session = \mod_vpl\seb\session_manager::get_or_create($record, (int)$USER->id, $starturl, true);
        }

        $config = \mod_vpl\seb\session_manager::build_phase1_config_xml($record, $session, $starturl);
    
    } else {
        $config = \mod_vpl\seb\settings::build_download_config_xml( $record, $starturl );
    }

foreach ([
    'Cache-Control: private, max-age=1, no-transform',
    'Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT',
    'Pragma: no-cache',
    'Content-Disposition: attachment; filename=' . \mod_vpl\seb\settings::get_download_filename(),
    'Content-Type: application/seb',
] as $header) {
    header($header);
}

echo $config;
