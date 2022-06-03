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

define(
    [
        'jquery',
        'mod_vpl/vplutil',
    ],
    function($, VPLUtil) {
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
                    assert(!VPLUtil.isBinary('a.c'), 'isBinary');
                })();
                (function() {
                    assert(!VPLUtil.isBlockly('a.c'), 'isBlockly');
                })();
                (function() {
                    assert(VPLUtil.validFileName('a.c'), 'validFileName');
                })();
            }
        });
        runTests();
        return {
            start: function() {
                $(showResults);
            }
        };
    }
);
