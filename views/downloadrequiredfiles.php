<?php
/**
 * @version		$Id: downloadrequiredfiles.php,v 1.1 2012-06-05 23:22:09 juanca Exp $
 * @package		VPL. Download required files
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';

require_login();
$id = required_param('id',PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->password_check();
$vpl->network_check();
if(!$vpl->is_visible()){
	notice(get_string('notavailable'));
}
$filegroup=$vpl->get_required_fgm();
$filegroup->download_files($vpl->get_printable_name());
die;
?>