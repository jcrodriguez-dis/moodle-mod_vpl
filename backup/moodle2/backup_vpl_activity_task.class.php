<?php
/**
 * VPL backup task class that provides all the settings and steps to perform one
 * complete backup of the activity
 *
 * @version		$Id: backup_vpl_activity_task.class.php,v 1.2 2012-06-20 14:55:49 juanca Exp $
 * @package		VPL
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__).'/backup_vpl_stepslib.php';

class backup_vpl_activity_task extends backup_activity_task {

	/**
	 * Define (add) particular settings this activity can have
	 */
	protected function define_my_settings() {
		// No particular settings for this activity
	}

	/**
	 * Define (add) particular steps this activity can have
	 */
	protected function define_my_steps() {
		$this->add_step(new backup_vpl_activity_structure_step('vpl_structure', 'vpl.xml'));
	}

	/**
	 * Code the transformations to perform in the activity in
	 * order to get transportable (encoded) links
	 */
	static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of VPL instances
        $search="/(".$base."\/mod\/vpl\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@VPLINDEX*$2@$', $content);

        // Link to VPL view by moduleid
        $search="/(".$base."\/mod\/vpl\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@VPLVIEWBYID*$2@$', $content);

        return $content;
	}
}
