<?php 
/**
 * @version		$Id: settings.php,v 1.26 2012-09-24 15:16:36 juanca Exp $
 * @package		VPL. Module common settings
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot.'/mod/vpl/lib.php';

$settings->add(new admin_setting_heading('heading1','',get_string('maxresourcelimits',VPL)));
$list = vpl_get_select_sizes(1024,1024*1024);
$default = vpl_get_array_key($list,1024*256);
$settings->add(new admin_setting_configselect('vpl_maxfilesize', get_string('maxfilesize', VPL),
                   get_string('maxfilesize', VPL), $default, $list));
$list = vpl_get_select_time();
$default = vpl_get_array_key($list,8*60);
$settings->add(new admin_setting_configselect('vpl_maxexetime', get_string('maxexetime', VPL),
                   get_string('maxexetime', VPL), $default, $list));
$list = vpl_get_select_sizes(1024*256,256*1024*1024);
$default = vpl_get_array_key($list,8*1024*1024);
$settings->add(new admin_setting_configselect('vpl_maxexefilesize', get_string('maxexefilesize', VPL),
                   get_string('maxexefilesize', VPL), $default, $list));
$list = vpl_get_select_sizes(64*1024*1024);
$default = vpl_get_array_key($list,512*1024*1024);
$settings->add(new admin_setting_configselect('vpl_maxexememory', get_string('maxexememory', VPL),
                   get_string('maxexememory', VPL), $default, $list));
$settings->add(new admin_setting_configtext('vpl_maxexeprocesses', get_string('maxexeprocesses', VPL),
                   get_string('maxexeprocesses', VPL),100, PARAM_INT ,4));


$settings->add(new admin_setting_heading('headingd','',get_string('defaultresourcelimits',VPL)));
                   $list = vpl_get_select_sizes(vpl_get_max_post_size());
$list = vpl_get_select_sizes(1024,1024*1024);
$default = vpl_get_array_key($list,1024*16);
$name='defaultfilesize';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL),
                   get_string($name, VPL), $default, $list));
$list = vpl_get_select_time();
$default = vpl_get_array_key($list,2*60);
$name='defaultexetime';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL),
                   get_string($name, VPL), $default, $list));
$list = vpl_get_select_sizes(1024*256,32*1024*1024);
$default = vpl_get_array_key($list,4*1024*1024);
$name='defaultexefilesize';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL),
                   get_string($name, VPL), $default, $list));
$list = vpl_get_select_sizes(256*1024*1024);
$default = vpl_get_array_key($list,192*1024*1024);
$name='defaultexememory';
$settings->add(new admin_setting_configselect('vpl_'.$name, get_string($name, VPL),
                   get_string($name, VPL), $default, $list));
$name='defaultexeprocesses';
$settings->add(new admin_setting_configtext('vpl_'.$name, get_string($name, VPL),
                   get_string($name, VPL),50, PARAM_INT ,4));
                   
$settings->add(new admin_setting_heading('heading2','',get_string('jail_servers_config',VPL)));
$default = "#This server is only for test use. "
			."Install your own Jail server and remove de following line as soon as posible\n".
			'http://demojail.dis.ulpgc.es:52000';
$settings->add(new admin_setting_configtextarea('vpl_jail_servers',
               get_string('jail_servers', VPL),get_string('jail_servers_description', VPL),$default));
$settings->add(new admin_setting_configtext('vpl_proxy_port_from', get_string('proxy_port_from', VPL),
                    get_string('proxy_port_from_description', VPL), 51001, PARAM_INT,6));
$settings->add(new admin_setting_configtext('vpl_proxy_port_to', get_string('proxy_port_to', VPL),
                    get_string('proxy_port_to_description', VPL), 51500, PARAM_INT,6));

$settings->add(new admin_setting_heading('heading3','',get_string('miscellaneous')));
$list = vpl_get_select_time();
$default = vpl_get_array_key($list,60);
$settings->add(new admin_setting_configcheckbox('vpl_direct_applet',
               get_string('direct_applet', VPL),
               get_string('direct_applet_description', VPL),1));
$settings->add(new admin_setting_configselect('vpl_discard_submission_period',
               get_string('discard_submission_period', VPL),
               get_string('discard_submission_period_description', VPL),$default,$list));
               
?>
