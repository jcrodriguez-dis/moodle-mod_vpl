<?php
/**
 * @version		$Id: similarity_form.php,v 1.7 2012-06-05 23:22:10 juanca Exp $
 * @package		VPL. Similarity form
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/../../../config.php';
require_once dirname(__FILE__).'/../locallib.php';
require_once dirname(__FILE__).'/../vpl.class.php';
require_once dirname(__FILE__).'/../vpl_submission.class.php';
require_once dirname(__FILE__).'/watermark.php';
require_once $CFG->libdir.'/formslib.php';
require_once dirname(__FILE__).'/similarity_form.class.php';

$id = required_param('id', PARAM_INT);
$vpl = new mod_vpl($id);
$vpl->prepare_page('similarity/similarity_form.php', array('id' => $id));

$vpl->require_capability(VPL_SIMILARITY_CAPABILITY);
$vpl->add_to_log('similarity form', vpl_rel_url('similarity/similarity_form.php','id',$id));
//Print header
$vpl->print_header(get_string('similarity',VPL));
$vpl->print_view_tabs(basename(__FILE__));
$form = new vpl_similarity_form('listsimilarity.php',$vpl);
$form->display();
$vpl->print_footer();
?>