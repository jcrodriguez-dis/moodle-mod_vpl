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
 * VPL JavaScript test
 *
 * @copyright 2017 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

import $ from 'jquery';
import {VPLUtil} from 'mod_vpl/vplutil';

var tests = [];
var result = [];
var testing = '';
var nAsserts = 0;

/**
 * Show results of tests in page and window.console
 */
function showResults() {
    var stat = nAsserts + " asserts tested";
    window.console.log(stat);
    $('#test_results').append($('<p></p>').text(stat));
    var message;
    if (result.length == 0) {
        message = 'Test passed';
    } else {
        message = result.length + ' errors found';
    }
    window.console.log(message);
    $('#test_results').append($('<p></p>').text(message));
    if (result.length > 0) {
        var list = $('<ol></ol>');
        for (var i = 0; i < result.length; i++) {
            window.console.log((i + 1) + ': ' + result[i]);
            var element = $('<li></li>');
            element.text(result[i]);
            list.append(element);
        }
        $('#test_results').append(list);
    }
}

/**
 * Basic test assert
 * @param {boolean} b test result
 * @param {string} message Optional message
 */
function assert(b, message) {
    nAsserts++;
    if (!b) {
        if (typeof message == 'string') {
            result.push("Error: " + message + " testing " + testing);
        }
    }
}

/**
 * Basic test assert
 * @param {boolean} a Expected
 * @param {boolean} b Actual
 * @param {string} message Optional message
 */
function assertEquals(a, b, message) {
    nAsserts++;
    if (a != b) {
        if (typeof message == 'string') {
            result.push("Error: " + message + " testing " + testing + '. Expected "' + a + '" found "' + b + '"');
        }
    }
}

tests.push({
    'name': "VPLUtil",
    'test': function() {
        assert(VPLUtil.returnTrue(), 'returnTrue');
        assert(!VPLUtil.returnFalse(), 'returnFalse');
        (function() {
            var rawData = new ArrayBuffer(13);
            assertEquals(13, rawData.byteLength, 'rawData.byteLength');
            var bufferData = new Uint8Array(rawData);
            for (var i = 0; i < bufferData.length; i++) {
                bufferData[i] = Math.round(Math.random() * 255);
            }
            assertEquals(13, bufferData.length, 'bufferData.length');
            var stringData = "abcdeñhfjéÇ123143565387095609784";
            var rawResult = VPLUtil.String2ArrayBuffer(stringData);
            var stringResult = VPLUtil.ArrayBuffer2String(rawResult);
            assertEquals(stringData, stringResult, 'ArrayBuffer2String');
            stringResult = VPLUtil.ArrayBuffer2String(rawData);
            rawResult = VPLUtil.String2ArrayBuffer(stringResult);
            var bufferResult = new Uint8Array(rawResult);
            assertEquals(bufferData.length, bufferResult.length, 'String2ArrayBuffer');
            if (bufferData.length == bufferResult.length) {
                for (var j = 0; j < bufferData.length; j++) {
                    assertEquals(bufferData[j], bufferResult[j], 'String2ArrayBuffer values');
                }
            }
        })();
        (function() {
            assertEquals('c', VPLUtil.fileExtension('a.c'), 'fileExtension');
            assertEquals('C', VPLUtil.fileExtension('a.c.C'), 'fileExtension');
            assertEquals('hxx', VPLUtil.fileExtension('a.hxx'), 'fileExtension');
            assertEquals('all', VPLUtil.fileExtension('.all'), 'fileExtension');
        })();
        (function() {
            assert(!VPLUtil.isImage('a.c'), 'isImage');
        })();
        (function() {
            assert(VPLUtil.isImage('a.png'), 'isImage');
        })();
        (function() {
            assert(!VPLUtil.isBinary('a.c'), 'isBinary');
        })();
        (function() {
            assert(VPLUtil.isBinary('a.pdf'), 'isBinary');
        })();
        (function() {
            assert(VPLUtil.isBinary('a.png'), 'isBinary');
        })();
        (function() {
            // Test isBinary with text content
            var textContent = 'int main() {\n    return 0;\n}';
            assert(!VPLUtil.isBinary('test.unknown', textContent), 'isBinary with text content');
        })();
        (function() {
            // Test isBinary with binary content (null byte)
            var binaryContent = 'text\x00binary';
            assert(VPLUtil.isBinary('test.unknown', binaryContent), 'isBinary with null byte');
        })();
        (function() {
            // Test isBinary with Uint8Array text content
            var encoder = new TextEncoder();
            var textArray = encoder.encode('Hello World');
            assert(!VPLUtil.isBinary('test.unknown', textArray), 'isBinary with Uint8Array text');
        })();
        (function() {
            // Test isBinary with Uint8Array binary content
            var binaryArray = new Uint8Array([0x00, 0x01, 0x02, 0x03]);
            assert(VPLUtil.isBinary('test.unknown', binaryArray), 'isBinary with Uint8Array binary');
        })();
        (function() {
            // Test isBinary with ArrayBuffer
            var buffer = new ArrayBuffer(4);
            var view = new Uint8Array(buffer);
            view[0] = 72; // 'H'
            view[1] = 105; // 'i'
            view[2] = 33; // '!'
            view[3] = 10; // '\n'
            assert(!VPLUtil.isBinary('test.unknown', buffer), 'isBinary with ArrayBuffer text');
        })();
        (function() {
            // Test isBinary with mixed content (mostly text with control chars)
            var mixedContent = 'Some text\x05with control chars';
            assert(VPLUtil.isBinary('test.unknown', mixedContent), 'isBinary with control chars');
        })();
        (function() {
            // Test isBinary falls back to extension when no content provided
            assert(VPLUtil.isBinary('test.pdf', undefined), 'isBinary extension fallback');
            assert(!VPLUtil.isBinary('test.txt', undefined), 'isBinary extension fallback for text');
        })();
        (function() {
            assert(!VPLUtil.isBlockly('a.c'), 'isBlockly');
        })();
        (function() {
            assert(VPLUtil.validFileName('a.c'), 'validFileName');
        })();
    }
});

tests.push({
    'name': "VPLUtil langType",
    'test': function() {
        var mapnames = VPLUtil.getLangNames();
        for (var ext in mapnames) {
            var filename;
            if (ext == 'plain_text') {
                continue;
            }
            if (ext.startsWith('.')) {
                filename = ext.substring(1);
            } else {
                filename = 'filename.' + ext;
            }
            assert(VPLUtil.langType(filename) != 'plain_text', 'VPLUtil.langType');
        }
        assert(VPLUtil.langType('otra_cosa') == 'plain_text', 'VPLUtil.langType');
    }
});

/**
 * Run tests in tests array
 */
function runTests() {
    for (var i = 0; i < tests.length; i++) {
        try {
            testing = tests[i].name;
            tests[i].test();
        } catch (e) {
            result.push("Error: Exception " + e.message + " testing " + testing + "\n" + e.stack);
        }
    }
}

export const start = () => {
    runTests();
    showResults();
};
