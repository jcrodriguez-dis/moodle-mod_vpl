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
 * Binary files extension methods. Add to VPLFile object.
 *
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

define(
    [
        'jquery',
        'mod_vpl/vplutil',
    ],
    function($, VPLUtil) {
        return function() {
            this.isBinary = function() {
                return true;
            };
            var setOldContent = this.setContent;
            this.setContent = function(c) {
                setOldContent.call(this, c);
                this.setModified();
                this.updateDataURL();
            };
            this.updateDataURL = function() {
                var fileName = this.getFileName();
                var value = this.getContent();
                var tid = this.getTId();
                if (VPLUtil.isImage(fileName)) {
                    var prevalue = 'data:' + VPLUtil.getMIME(fileName) + ';base64,';
                    $(tid).find('img').attr('src', prevalue + value);
                } else {
                    $(tid).find('img').attr('src', '');
                }
            };
            this.adjustSize = function() {
                if (!this.isOpen()) {
                    return false;
                }
                var editTag = $(this.getTId());
                if (editTag.length === 0) {
                    return false;
                }
                var tabs = editTag.parent();
                var newHeight = tabs.height();
                newHeight -= editTag.position().top;
                if (newHeight != editTag.height()) {
                    editTag.height(newHeight);
                    return true;
                }
                return false;
            };
            this.open = function() {
                this.showFileName();
                var fileName = this.getFileName();
                var tid = this.getTId();
                this.setOpen(true);
                if (VPLUtil.isImage(fileName)) {
                    $(tid).addClass('vpl_ide_img').append('<img />');
                    this.updateDataURL();
                } else {
                    $(tid).addClass('vpl_ide_binary').text(VPLUtil.str('binaryfile'));
                }
            };
            this.close = function() {
                this.setOpen(false);
            };
            this.langSelection = function() {
                this.setLang('Binary');
            };
        };
    }
);
