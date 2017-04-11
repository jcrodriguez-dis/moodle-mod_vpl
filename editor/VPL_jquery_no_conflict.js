/**
 * Use Own JQuery
 *
 * @package mod_vpl
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
// Get correct version of jQuery for use without conflict in VPL.
// This must be run after loading VPL jQuery.

/* globals $JQVPL: true */
/* globals jQuery */

$JQVPL = jQuery.noConflict(true);
