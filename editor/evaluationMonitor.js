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
 * Evaluation monitoring
 * @package mod_vpl
 * @copyright 2013 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/* globals VPL_Single_Evaluation: true */
/* globals VPL_Util */

(function() {
    VPL_Single_Evaluation = function(options) {
        VPL_Util.set_str(options.i18n);
        options.next = function() {
            window.location = options.nexturl;
        };
        function showErrorMessage(message) {
            VPL_Util.showErrorMessage(message, {
                next : options.next
            });
        }
        var action;
        var executionActions = {
            'ajaxurl' : options.ajaxurl,
            'run' : showErrorMessage,
            'getLastAction' : function() {
                action();
            },
        };
        action = function() {
            VPL_Util.requestAction('evaluate', 'evaluating', {}, options.ajaxurl)
            .done(
                    function(response) {
                        VPL_Util.webSocketMonitor(response, 'evaluate', 'evaluating', executionActions)
                        .done(options.next)
                        .fail(showErrorMessage);
                    }
            )
            .fail(showErrorMessage);
        };
        action();
    };
})();
