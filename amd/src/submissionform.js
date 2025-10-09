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
 * Submission form utility.
 * @copyright 2024 Astor Bizard
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

export const setup = () => {
    var updateHeaders = function(submitType) {
        var archive = submitType == 'archive';
        $('#id_headersubmitarchive').toggle(archive).toggleClass('collapsed', !archive);
        $('#id_headersubmitfiles').toggle(!archive).toggleClass('collapsed', archive);
    };
    var $select = $('select[name="submitmethod"]');
    $select.change(function() {
        updateHeaders($select.val());
    });
    updateHeaders($select.val());
};
