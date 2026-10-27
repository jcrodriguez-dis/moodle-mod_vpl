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
import {VPLUI} from 'mod_vpl/vplui';
import {VPLLSClient} from 'mod_vpl/vpllsclient';
import {VPLIDEButtons} from 'mod_vpl/vplidebutton';

var tests = [];
var result = [];
var testing = '';
var nAsserts = 0;
var suiteAssertsAtStart = 0;
var finished = false;
const schedule = window.setTimeout.bind(window);

/**
 * Show the final result without replacing the suite and failure details.
 */
function showResults() {
    var passed = result.length === 0;
    var message = nAsserts + ' assertions tested. ' + (passed ? 'Test passed' : result.length + ' errors found');
    window.console.log(message);
    $('#vpl_js_summary').text(message);
    $('#vpl_js_progress').text('Finished ' + tests.length + ' JavaScript test suites.');
    $('#test_results').attr('data-status', passed ? 'passed' : 'failed');
}

/**
 * Publish failures immediately so an unfinished run still has useful diagnostics.
 * @param {string} message Failure description
 * @param {*} error Original exception, when available
 */
function recordFailure(message, error = new Error(message)) {
    var detail = 'Error: [' + (testing || 'JavaScript runner') + '] ' + message;
    if (error && error.stack) {
        detail += '\n' + error.stack;
    }
    result.push(detail);
    window.console.error(detail);
    $('#vpl_js_failures').append($('<li></li>').append($('<pre></pre>').text(detail)));
    $('#vpl_js_summary').text(result.length + ' errors found so far.');
    if (finished) {
        $('#vpl_js_suites > li').last().attr('data-status', 'failed')
            .text('Failed: ' + testing + ' (asynchronous error; see failure details)');
        showResults();
    }
}

/**
 * Report asynchronous errors outside an awaited test.
 * @param {ErrorEvent} event Browser error event
 */
function onError(event) {
    recordFailure('Uncaught error: ' + (event.error ? String(event.error) : event.message), event.error);
}

/**
 * Report promises rejected outside an awaited test.
 * @param {PromiseRejectionEvent} event Browser rejection event
 */
function onRejection(event) {
    recordFailure('Unhandled rejection: ' + String(event.reason), event.reason);
}

/**
 * Basic test assert
 * @param {boolean} b test result
 * @param {string} message Optional message
 */
function assert(b, message) {
    nAsserts++;
    if (!b) {
        recordFailure('Assertion ' + (nAsserts - suiteAssertsAtStart) + ' (total ' + nAsserts + '): ' +
            (message || 'assert failed') + '. Expected true, found ' + String(b));
    }
}

/**
 * Basic test assert
 * @param {*} a Expected
 * @param {*} b Actual
 * @param {string} message Optional message
 */
function assertEquals(a, b, message) {
    nAsserts++;
    if (a != b) {
        recordFailure('Assertion ' + (nAsserts - suiteAssertsAtStart) + ' (total ' + nAsserts + '): ' +
            (message || 'assertEquals failed') + '. Expected (' + typeof a + ') "' + String(a) +
            '", found (' + typeof b + ') "' + String(b) + '"');
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
    'name': "VPLUtil file icon initialization",
    'test': function() {
        var options = VPLUtil.options;
        var config = options.fileIcons;
        var fixture = $('<div></div>').attr({
            id: 'vpl_test_file_icons',
            'data-file-icons': JSON.stringify(config)
        }).appendTo('body');
        var args = {scriptPath: options.scriptPath, fileIconsElement: 'vpl_test_file_icons'};
        try {
            assert(JSON.stringify([args]).length <= 1024, 'AMD initialization arguments stay below the Moodle limit');
            VPLUtil.init(args);
            assertEquals(JSON.stringify(config), JSON.stringify(VPLUtil.options.fileIcons), 'Icon mappings from data attribute');
            assertEquals('python', VPLUtil.getFileIconName('main.py'), 'Icon selection after initialization');
        } finally {
            VPLUtil.options = options;
            fixture.remove();
        }
    }
});

tests.push({
    'name': "VPLUtil file icons",
    'test': function() {
        var cases = {
            'src/Makefile': 'make',
            'src/makefile': 'make',
            'source.c': 'c',
            'source.C': 'cpp',
            'include/source.h': 'cpp',
            'source.s': 'mips',
            'source.py': 'python',
            'source.tsx': 'typescript',
            'source.mzn': 'minizinc',
            'source.rust': 'rust',
            'photo.PNG': 'image',
            'recording.MP3': 'audio',
            'movie.webm': 'video',
            'archive.zip': 'binary',
            'workspace.blockly2': 'blockly',
            'workspace.BLOCKLY2': 'blockly',
            'vpl_evaluate.cases': 'cases',
            'vpl_run.sh.bak': 'text',
            'script.sh': 'shell',
            'VPL_RUN.sh': 'shell',
            'unknown.unknown': 'text',
            'constructor': 'text',
            'source.toString': 'text'
        };
        Object.keys(cases).forEach(function(filename) {
            assertEquals(cases[filename], VPLUtil.getFileIconName(filename), filename);
        });
        var config = VPLUtil.options.fileIcons;
        Object.keys(config.extensions).forEach(function(extension) {
            assertEquals(config.extensions[extension], VPLUtil.getFileIconName('src/file.' + extension), extension);
        });
        config.scripts.forEach(function(filename) {
            assertEquals('vplscript', VPLUtil.getFileIconName('execution/' + filename), filename);
            assertEquals('binary', VPLUtil.getFileIconName(filename, true), filename + ' binary');
        });
        assertEquals('binary', VPLUtil.getFileIconName('unknown.unknown', true), 'Binary contents');
        assertEquals('image', VPLUtil.getFileIconName('photo.png', true), 'Image before binary');
        assertEquals('audio', VPLUtil.getFileIconName('sound.mp3', true), 'Audio before binary');
        assertEquals('video', VPLUtil.getFileIconName('movie.webm', true), 'Video before binary');
    }
});

tests.push({
    'name': "VPLUI file icons",
    'test': function() {
        var cases = [
            ['src/main.py', false, 'python'],
            ['execution/vpl_run.sh', false, 'vplscript'],
            ['unknown.unknown', true, 'binary'],
            ['photo.png', true, 'image'],
        ];
        cases.forEach(function(entry) {
            var icon = $(VPLUI.iconFile(entry[0], entry[1]));
            assertEquals('img', icon.prop('tagName').toLowerCase(), entry[0] + ' icon element');
            assert(icon.attr('src').endsWith('/mod/vpl/pix/fileicons/' + entry[2] + '.svg'), entry[0] + ' icon URL');
            assertEquals('', icon.attr('alt'), entry[0] + ' decorative icon');
            assertEquals('true', icon.attr('aria-hidden'), entry[0] + ' hidden from screen readers');
            assert(icon.hasClass('vpl_ide_fileicon'), entry[0] + ' icon class');
        });
    }
});

tests.push({
    'name': "VPLUI annotation status",
    'test': function() {
        var fixture = $('<div id="vpl_ide_statusbar"><span class="vpl_ide_statusbar_annotations"></span></div>')
            .appendTo('body');
        var panel = fixture.find('.vpl_ide_statusbar_annotations');
        var lines = [];
        var annotations = [
            {type: 'error', row: 9},
            {type: 'warning', row: 7},
            {type: 'info', row: 4},
            {type: 'error', row: 2},
            {type: 'warning', row: 1},
            {type: 'hint', row: 0}
        ];
        try {
            VPLUI.updateIDEStatus({
                annotations: annotations,
                gotoAnnotation: function(line) {
                    lines.push(line);
                }
            });
            assertEquals(3, panel.find('button').length, 'One button for each annotation type');
            assertEquals('221', panel.text(), 'Only counts are visible');
            ['error', 'warning', 'info'].forEach(function(type) {
                var button = panel.find('[data-type="' + type + '"]');
                assertEquals('button', button.attr('type'), type + ' button does not submit forms');
                assertEquals(1, button.find('.ace_icon.ace_' + type).length, type + ' uses the Ace icon');
                assertEquals('true', button.find('.ace_icon').attr('aria-hidden'), type + ' decorative icon');
                assert(button.attr('aria-label').includes(VPLUtil.str(type)), type + ' accessible label');
                button.find('.ace_icon').trigger('click');
                button.find('.vpl_ide_annotation_count').trigger('click');
            });
            assertEquals('3,3,2,2,5,5', lines.join(','), 'Icon and count navigate to the earliest row of each type');
            VPLUI.updateIDEStatus({lsp: 'LS'});
            assertEquals('221', panel.text(), 'Partial status updates preserve annotation counts');
            VPLUI.updateIDEStatus({annotations: [], gotoAnnotation: VPLUtil.doNothing});
            assertEquals('', panel.text(), 'Empty code files do not show zero counts');
            assertEquals(0, panel.children().length, 'Zero counts do not create buttons');
            assertEquals('none', panel.css('display'), 'Empty code files hide the annotation panel');
            var nextLine = 0;
            VPLUI.updateIDEStatus({
                annotations: [{type: 'error', row: 0}],
                gotoAnnotation: function(line) {
                    nextLine = line;
                }
            });
            assertEquals(1, panel.find('button').length, 'Only annotation types with issues have buttons');
            panel.find('[data-type="error"]').trigger('click');
            assertEquals(1, nextLine, 'Switching files replaces navigation callbacks');
            assertEquals(6, lines.length, 'Previous file navigation is not reused');
            VPLUI.updateIDEStatus({fileName: 'photo.png', binary: true});
            assertEquals('none', panel.css('display'), 'Non-code files hide the panel');
            assertEquals(0, panel.children().length, 'Non-code files remove stale navigation');
            VPLUI.updateIDEStatus({annotations: annotations, gotoAnnotation: VPLUtil.doNothing});
            VPLUI.clearIDEStatus();
            assertEquals('none', panel.css('display'), 'Closing files hides the panel');
            assertEquals(0, panel.children().length, 'Clearing status removes navigation');
        } finally {
            fixture.remove();
        }
    }
});

tests.push({
    'name': "LS shortcut auto-repeat",
    'test': function() {
        const fixture = $('<div tabindex="0"><div class="menu"></div></div>').appendTo('body');
        const originalOn = $.fn.on;
        let buttons;
        try {
            // Keep the shortcut listener inside the disposable fixture.
            $.fn.on = function(...args) {
                return originalOn.apply(this[0] === document.body && args[0] === 'keydown' ? fixture : this, args);
            };
            buttons = new VPLIDEButtons(fixture.find('.menu'), VPLUtil.returnTrue);
        } finally {
            $.fn.on = originalOn;
        }
        let requests = 0;
        let macRequests = 0;
        let regularActions = 0;
        /**
         * Dispatch a native key event so jQuery exposes repeat through originalEvent.
         * @param {string} key Pressed key
         * @param {number} which Legacy key code used by the shortcut dispatcher
         * @param {Object} options Modifiers and auto-repeat flag
         * @returns {KeyboardEvent} Dispatched event
         */
        function press(key, which, options = {}) {
            const event = new window.KeyboardEvent('keydown', {
                key: key, keyCode: which, which: which, bubbles: true, cancelable: true, ...options
            });
            fixture[0].dispatchEvent(event);
            return event;
        }
        try {
            buttons.add({
                name: 'testLS', bindKey: {win: 'Ctrl-.', mac: 'Ctrl-.'}, allowRepeat: false,
                originalAction: () => requests++
            });
            buttons.add({
                name: 'testMacLS', bindKey: {win: 'Command-.', mac: 'Command-.'}, allowRepeat: false,
                originalAction: () => macRequests++
            });
            buttons.add({
                name: 'testRegular', bindKey: {win: 'Ctrl-L', mac: 'Ctrl-L'},
                originalAction: () => regularActions++
            });
            assert(press('.', 190, {ctrlKey: true}).defaultPrevented, 'Initial LS shortcut is consumed');
            assertEquals(1, requests, 'Initial LS shortcut runs once');
            let allConsumed = true;
            for (let index = 0; index < 100; index++) {
                allConsumed = press('.', 190, {ctrlKey: true, repeat: true}).defaultPrevented && allConsumed;
            }
            assertEquals(1, requests, 'Holding Ctrl-. does not generate more requests');
            assert(allConsumed, 'Suppressed repeats cannot fall through to another shortcut handler');
            fixture[0].dispatchEvent(new window.KeyboardEvent('keyup', {key: '.', bubbles: true}));
            press('.', 190, {ctrlKey: true});
            assertEquals(2, requests, 'A deliberate new key press runs immediately');
            press('.', 190, {metaKey: true});
            press('.', 190, {metaKey: true, repeat: true});
            assertEquals(1, macRequests, 'Command-. also ignores held-key auto-repeat');
            press('l', 76, {ctrlKey: true});
            press('l', 76, {ctrlKey: true, repeat: true});
            assertEquals(2, regularActions, 'Other IDE shortcuts retain auto-repeat by default');
            buttons.launchAction('testLS');
            buttons.launchAction('testLS');
            assertEquals(4, requests, 'Non-keyboard actions are not rate-limited');
            buttons.enable('testLS', false);
            press('.', 190, {ctrlKey: true});
            assertEquals(4, requests, 'Disabled actions remain disabled');
        } finally {
            fixture.remove();
        }
    }
});

tests.push({
    'name': "LSP usage statistics",
    'test': async function() {
        const originalRun = VPLUtil.directRun;
        const originalCancel = VPLUtil.cancelDirectRun;
        const originalNow = window.performance.now;
        const originalSetTimeout = window.setTimeout;
        const originalClearTimeout = window.clearTimeout;
        let now = 1000;
        let nextTimer = 1000000;
        const timers = new Map();
        const sockets = [];
        const manager = {getFiles: () => [], currentFile: () => false};
        const first = new VPLLSClient('', manager, 'cpp', 'en');
        const second = new VPLLSClient('', manager, 'python', 'en');
        first.initializeRequest = VPLUtil.doNothing;
        second.initializeRequest = VPLUtil.doNothing;
        const encoder = new TextEncoder();
        window.performance.now = () => now;
        window.setTimeout = function(callback, delay) {
            const id = nextTimer++;
            timers.set(id, {callback: callback, delay: delay});
            return id;
        };
        window.clearTimeout = function(id) {
            if (!timers.delete(id)) {
                originalClearTimeout.call(window, id);
            }
        };
        VPLUtil.directRun = function() {
            const socket = {
                readyState: 1, CONNECTING: 0, OPEN: 1, CLOSING: 2, CLOSED: 3,
                sent: [],
                send: function(message) {
                    this.sent.push(message);
                },
                close: function() {
                    this.readyState = 3;
                }
            };
            sockets.push(socket);
            return Promise.resolve({connection: socket, processid: sockets.length, homepath: '/home/test'});
        };
        VPLUtil.cancelDirectRun = () => Promise.resolve();
        /**
         * Frame an incoming message with its actual UTF-8 Content-Length.
         * @param {Object} message JSON-RPC message
         * @returns {string} Framed message
         */
        function frame(message) {
            const body = JSON.stringify(message);
            return 'Content-Length: ' + encoder.encode(body).byteLength + '\r\n\r\n' + body;
        }
        let receivedBytes = 0;
        /**
         * Deliver one WebSocket text frame and track its expected size independently.
         * @param {string} data Incoming data
         */
        function receive(data) {
            receivedBytes += encoder.encode(data).byteLength;
            sockets[0].onmessage({data: data});
        }
        try {
            first.startConnection();
            second.startConnection();
            await Promise.resolve();
            assertEquals(0, first.getStatistics().runningTime, 'Running time starts at process confirmation');
            now = 1010.25;
            const requestA = first.sendRequest('textDocument/hover', {text: '\u00e9\ud83d\ude80'});
            now = 1020.25;
            const requestB = first.sendRequest('textDocument/completion', {});
            first.sendNotification('test/notification', {text: '\u00e9\ud83d\ude80'});
            now = 1030;
            receive(frame({jsonrpc: '2.0', id: 1, method: 'client/registerCapability', params: {registrations: []}}));
            assertEquals(2, first.getStatistics().pendingRequests, 'Server request ID cannot consume a client request');
            receive(frame({jsonrpc: '2.0', method: 'test/notification', params: {text: '\u00e9\ud83d\ude80'}}));
            now = 1040;
            assertEquals(49.5, first.getStatistics().responseWaitTime, 'Concurrent in-flight waits are included');
            assertEquals(null, first.getStatistics().averageResponseTime, 'No average before a matching response');
            const responseB = frame({jsonrpc: '2.0', id: 2, result: []});
            now = 1050;
            receive(responseB.substring(0, 10));
            assertEquals(0, first.getStatistics().responsesReceived, 'Partial frames do not count as messages');
            now = 1060.5;
            receive(responseB.substring(10));
            await requestB;
            now = 1110.75;
            receive(frame({jsonrpc: '2.0', id: 1, error: {code: -32603, message: 'test error'}}));
            await requestA;
            let stats = first.getStatistics();
            assertEquals(110.75, stats.runningTime, 'Monotonic running time');
            assertEquals(140.75, stats.responseWaitTime, 'Cumulative wait includes error responses');
            assertEquals(70.375, stats.averageResponseTime, 'Average of matched responses keeps submillisecond precision');
            assertEquals(2, stats.requestsSent, 'Requests sent');
            assertEquals(1, stats.requestsReceived, 'Server requests received');
            assertEquals(1, stats.responsesSent, 'Responses to server requests sent');
            assertEquals(2, stats.responsesReceived, 'Responses received');
            assertEquals(1, stats.notificationsSent, 'Notifications sent');
            assertEquals(1, stats.notificationsReceived, 'Notifications received');
            assertEquals(receivedBytes, stats.bytesReceived, 'Received bytes include Unicode and split framing');
            assertEquals(sockets[0].sent.reduce((size, data) => size + encoder.encode(data).byteLength, 0),
                stats.bytesSent, 'Sent bytes include UTF-8 contents and headers');
            assertEquals(0, second.getStatistics().bytesSent, 'Instances have independent byte counters');
            assertEquals(0, second.getStatistics().responsesReceived, 'Instances have independent message counters');
            now = 1120.75;
            const timedOut = first.sendRequest('textDocument/hover', {});
            const timeout = Array.from(timers.values()).find(timer => timer.delay === 30000);
            if (!timeout) {
                throw new Error('Request timeout timer was not registered');
            }
            now = 31120.75;
            timeout.callback();
            await timedOut;
            assertEquals(30140.75, first.getStatistics().responseWaitTime, 'Timeouts contribute their actual wait');
            assertEquals(70.375, first.getStatistics().averageResponseTime, 'Timeouts do not dilute response average');
            receive(frame({jsonrpc: '2.0', id: 3, result: null}));
            assertEquals(3, first.getStatistics().responsesReceived, 'Late responses still count as received messages');
            assertEquals(70.375, first.getStatistics().averageResponseTime, 'Late responses do not double-count timing');
            now = 31130.75;
            const interrupted = first.sendRequest('textDocument/hover', {});
            receive('Content-Le');
            const oldMessageHandler = sockets[0].onmessage;
            now = 31180.75;
            sockets[0].readyState = 3;
            sockets[0].onclose();
            await interrupted;
            assertEquals(30190.75, first.getStatistics().responseWaitTime, 'Disconnect settles pending waits');
            now = 40000;
            assertEquals(30180.75, first.getStatistics().runningTime, 'Running time freezes at disconnection');
            assertEquals(39000, second.getStatistics().runningTime, 'Other server running time continues');
            now = 50000;
            first.startConnection();
            assertEquals(0, first.getStatistics().bytesReceived, 'Reconnect immediately resets byte counters');
            assertEquals(0, first.getStatistics().responseWaitTime, 'Reconnect resets waiting times');
            assertEquals(null, first.getStatistics().averageResponseTime, 'Reconnect resets response average');
            await Promise.resolve();
            oldMessageHandler({data: frame({method: 'test/stale', jsonrpc: '2.0'})});
            assertEquals(0, first.getStatistics().bytesReceived, 'Old socket events cannot pollute new statistics');
            now = 50010;
            const fresh = first.sendRequest('textDocument/hover', {});
            now = 50020;
            const freshResponse = frame({jsonrpc: '2.0', id: 1, result: null});
            sockets[2].onmessage({data: freshResponse});
            await fresh;
            assertEquals(1, first.getStatistics().responsesReceived, 'Reconnect clears partial framing and request IDs');
            assertEquals(10, first.getStatistics().averageResponseTime, 'New session has independent timing');
            const send = sockets[2].send;
            sockets[2].send = function() {
                throw new Error('test send failure');
            };
            const bytesBeforeFailure = first.getStatistics().bytesSent;
            let rejected = false;
            try {
                await first.sendRequest('textDocument/hover', {});
            } catch (error) {
                rejected = error.message === 'test send failure';
            }
            assert(rejected, 'Send failures are propagated to the caller');
            assertEquals(bytesBeforeFailure, first.getStatistics().bytesSent, 'Failed sends do not count bytes');
            assertEquals(1, first.getStatistics().requestsSent, 'Failed sends do not count requests');
            assertEquals(0, first.getStatistics().pendingRequests, 'Failed sends do not leak pending requests');
            sockets[2].send = send;
            now = 50030.5;
            const stopped = first.sendRequest('textDocument/hover', {});
            now = 50040.75;
            first.stopConnection();
            await stopped;
            stats = first.getStatistics();
            assertEquals(20.25, stats.responseWaitTime, 'Intentional stop settles waiting time');
            assertEquals(40.75, stats.runningTime, 'Intentional stop freezes running time');
            assertEquals(0, stats.pendingRequests, 'Intentional stop clears pending requests');
            stats.bytesSent = -1;
            assert(first.getStatistics().bytesSent >= 0, 'Statistics snapshots cannot mutate the counters');
            const launches = [];
            VPLUtil.directRun = () => new Promise((resolve, reject) => launches.push({resolve: resolve, reject: reject}));
            first.startConnection();
            first.stopConnection();
            first.startConnection();
            const abandoned = {...sockets[0], readyState: 1};
            launches[0].resolve({connection: abandoned, processid: 100, homepath: '/home/abandoned'});
            await Promise.resolve();
            assertEquals(3, abandoned.readyState, 'Superseded launches close their sockets');
            assert(first.isConnecting(), 'Superseded launch cannot replace the current connection attempt');
            assertEquals(0, first.getStatistics().runningTime, 'Superseded launch cannot start the running clock');
            first.stopConnection();
            first.startConnection();
            launches[1].reject(new Error('test superseded launch failure'));
            await Promise.resolve();
            await Promise.resolve();
            assert(first.isConnecting(), 'Superseded launch failure cannot interrupt the current attempt');
            const current = {...sockets[0], readyState: 1};
            now = 60000;
            launches[2].resolve({connection: current, processid: 102, homepath: '/home/current'});
            await Promise.resolve();
            now = 60005;
            assertEquals(5, first.getStatistics().runningTime, 'Only the current launch starts the running clock');
            assertEquals(0, first.getStatistics().requestsSent, 'Superseded launches cannot restore previous counters');
        } finally {
            first.stopConnection();
            second.stopConnection();
            VPLUtil.directRun = originalRun;
            VPLUtil.cancelDirectRun = originalCancel;
            window.performance.now = originalNow;
            window.setTimeout = originalSetTimeout;
            window.clearTimeout = originalClearTimeout;
        }
    }
});

tests.push({
    'name': "LSP statistics formatting",
    'test': function() {
        const stats = {
            language: 'cpp', runningTime: 90000, responseWaitTime: 1234, averageResponseTime: 0.5,
            requestsSent: 3, requestsReceived: 4, responsesSent: 1, responsesReceived: 2,
            notificationsSent: 5, notificationsReceived: 6, bytesSent: 1024, bytesReceived: 1048576
        };
        let title = VPLUI.getLSStatisticsTitle(stats);
        assert(title.includes('1.50 min'), 'Running time in human-readable units');
        assert(title.includes('1.23 s'), 'Cumulative wait in seconds');
        assert(title.includes('0.5 ms'), 'Subsecond average response time');
        const lines = title.split('\n');
        assertEquals(VPLUtil.str('ls:clientrequests') + ': 3 / 2', lines[4], 'Client requests paired with server responses');
        assertEquals(VPLUtil.str('ls:serverrequests') + ': 4 / 1', lines[5], 'Server requests paired with client responses');
        assertEquals(VPLUtil.str('ls:notifications') + ' (' + VPLUtil.str('ls:sentreceived') + '): 5 / 6', lines[6],
            'Notifications retain independent directional counts');
        assert(title.includes('1.00 KB') && title.includes('1.00 MB'), 'Byte units');
        stats.runningTime = 7200000;
        stats.bytesSent = 0;
        stats.averageResponseTime = null;
        title = VPLUI.getLSStatisticsTitle(stats);
        assert(title.includes('2.00 h'), 'Long running time in hours');
        assert(title.includes('0 B'), 'Empty transfers in bytes');
        assert(title.includes(VPLUtil.str('ls:averageresponsetime') + ': -'), 'No misleading zero average before responses');
    }
});

tests.push({
    'name': "LSP completion ordering",
    'test': async function() {
        if (!window.ace || !window.ace.require('ace/autocomplete')) {
            await new Promise(resolve => VPLUtil.loadScript([
                '/../thirdpartylibs/ace/ace.js',
                '/../thirdpartylibs/ace/ext-language_tools.js'
            ], resolve));
        }
        const aceModule = window.ace.require('ace/autocomplete');
        const client = new VPLLSClient('', {}, 'cpp', 'en');
        client.isConnected = VPLUtil.returnTrue;
        let response;
        let prefix = '';
        const session = {
            $vplfile: {},
            getLine: () => prefix
        };
        const editor = {
            once: VPLUtil.doNothing,
            session: session,
            getSession: () => session,
            getCursorPosition: () => ({row: 0, column: prefix.length}),
            completers: [client.aceCompleterAdapter]
        };
        client.completionRequest = async function() {
            return {result: response};
        };
        client.aceCompleterAdapter.configure(editor, aceModule);
        /**
         * Request completions through the real Ace provider.
         * @param {Array|Object} items LSP completion response
         * @param {string} text Typed prefix
         * @returns {Promise<Object>} Filtered completion list
         */
        function complete(items, text) {
            response = items;
            prefix = text;
            return new Promise((resolve, reject) => {
                editor.completer.getCompletionProvider().provideCompletions(editor, {}, (error, completions, finished) => {
                    if (error) {
                        reject(error);
                    } else if (finished) {
                        resolve(completions);
                    }
                });
            });
        }
        /**
         * Get displayed captions in completion order.
         * @param {Object} list Filtered completion list
         * @returns {string} Captions
         */
        function captions(list) {
            return list.filtered.map(item => item.caption).join(',');
        }
        try {
            let list = await complete([
                {label: 'import', sortText: '000', preselect: true},
                {label: 'FloatingPointError', sortText: '100'},
                {label: 'ImportError', sortText: '200'},
                {label: 'posminimo', sortText: '900'}
            ], 'po');
            assertEquals('posminimo,import,ImportError,FloatingPointError', captions(list),
                'Prefix matches beat preselection and server priority, followed by fuzzy distance');
            list = await complete([
                {label: 'p_o_s', sortText: '000'},
                {label: 'xpos', sortText: '100'},
                {label: 'posminimo', sortText: '200'},
                {label: 'p_os', sortText: '300'}
            ], 'p');
            assertEquals('p_o_s,posminimo,p_os,xpos', captions(list), 'Server ranking breaks prefix-distance ties');
            list.setFilter('pos');
            assertEquals('posminimo,xpos,p_os,p_o_s', captions(list),
                'Typing reranks cached results by prefix, match position and gaps');
            list.setFilter('p');
            assertEquals('p_o_s,posminimo,p_os,xpos', captions(list), 'Backspace recomputes distance ranking');
            list = await complete([
                {label: 'xxPo', sortText: '000'},
                {label: 'POSMINIMO', sortText: '100'}
            ], 'po');
            assertEquals('POSMINIMO,xxPo', captions(list), 'Case-insensitive prefix matches remain ahead of substrings');
            list = await complete([
                {label: 'INFINITY', sortText: '000', preselect: true},
                {label: 'INPUT', sortText: '100'},
                {label: 'Input', sortText: '200'},
                {label: 'input', sortText: '300'},
                {label: 'int', sortText: '900'}
            ], 'in');
            assertEquals('input,int,Input,INFINITY,INPUT', captions(list),
                'Matching lowercase characters beat uppercase constants and preselection');
            list.setFilter('IN');
            assertEquals('INFINITY,INPUT,Input,input,int', captions(list),
                'Changing typed case reranks cached completions');
            list.setFilter('i');
            assertEquals('input,int,INFINITY,INPUT,Input', captions(list),
                'Backspace recomputes case penalties');
            list.setFilter('');
            assertEquals('INFINITY,INPUT,Input,input,int', captions(list), 'Empty prefix preserves server ranking');
            list = await complete([
                {label: 'aBc', sortText: '000'},
                {label: 'abc', sortText: '100'},
                {label: 'aBC', sortText: '200'}
            ], 'ac');
            assertEquals('aBc,abc,aBC', captions(list),
                'Only matched characters contribute to fuzzy case penalties');
            list.setFilter('aC');
            assertEquals('aBC,aBc,abc', captions(list), 'Fuzzy matching prefers the typed case');
            list = await complete([
                {label: 'xIN', sortText: '000'},
                {label: 'xin', sortText: '100'}
            ], 'in');
            assertEquals('xin,xIN', captions(list), 'Substring ranking accounts for matching case');
            list = await complete([
                {label: 'xIN', sortText: '000'},
                {label: 'xxin', sortText: '100'}
            ], 'in');
            assertEquals('xIN,xxin', captions(list), 'Fuzzy distance remains more important than case differences');
            list = await complete([
                {label: '\u00cdNT', sortText: '000'},
                {label: '\u00ednt', sortText: '100'}
            ], '\u00edn');
            assertEquals('\u00ednt,\u00cdNT', captions(list), 'Accented identifiers retain case preference');
            list = await complete([
                {label: 'INPUT', filterText: 'input', sortText: '100'},
                {label: 'int', filterText: 'INT', sortText: '000'}
            ], 'in');
            assertEquals('INPUT,int', captions(list), 'Case penalties use LSP filterText rather than the caption');
            list = await complete([
                {label: 'fooPrint', filterText: 'print', sortText: '000', preselect: true},
                {label: 'print', sortText: '100'}
            ], 'pri');
            assertEquals('fooPrint,print', captions(list), 'Server priority breaks ties using LSP filterText');
            list = await complete([
                {label: 'operator<<', filterText: 'print', sortText: '000', insertText: 'std::cout << '},
                {label: 'printf', sortText: '100'}
            ], 'pri');
            assertEquals('operator<<,printf', captions(list), 'LSP filterText matches independently of the caption');
            assertEquals('std::cout << ', list.filtered[0].value, 'Filtering preserves insertion text');
            assertEquals('print', list.filtered[0].lspItem.filterText, 'Original LSP item is preserved');
            list = await complete([
                {label: 'print', filterText: ''}, {label: 'printf'}
            ], 'pri');
            assertEquals('printf', captions(list), 'Empty filterText does not fall back to insertion text');
            list = await complete([{label: 'print', filterText: ''}], '');
            assertEquals('print', captions(list), 'Empty filterText can match an empty prefix');
            list = await complete({items: [
                {label: 'operator<<', filterText: 'printf', sortText: '000'},
                {label: 'print', sortText: '100'},
                {label: 'private', sortText: '200'},
                {label: 'process', sortText: '300'}
            ]}, 'pr');
            assertEquals('operator<<,print,private,process', captions(list), 'CompletionList response ordering');
            list.setFilter('prin');
            assertEquals('operator<<,print', captions(list), 'Typing narrows cached completions using filterText');
            list.setFilter('pr');
            assertEquals('operator<<,print,private,process', captions(list), 'Backspace restores cached completions');
            list.setFilter('pro');
            assertEquals('process', captions(list), 'A changed prefix removes nonmatching candidates');
            list.setFilter('missing');
            assertEquals(0, list.filtered.length, 'Unmatched candidates are not kept via skipFilter');
            list = await complete([
                {label: 'printf', sortText: '000'},
                {label: 'perror', sortText: '100'}
            ], 'p');
            assertEquals('printf,perror', captions(list), 'Explicit sortText beats alphabetical order');
            list = await complete([
                {label: 'printf'}, {label: 'perror'}, {label: 'puts'}
            ], 'p');
            assertEquals('perror,printf,puts', captions(list), 'Missing sortText follows the LSP label fallback');
            list = await complete([
                {label: 'alpha', sortText: '000'},
                {label: 'zeta', sortText: '100', preselect: true}
            ], '');
            assertEquals('zeta,alpha', captions(list), 'Preselected matching item is first');
            list = await complete([
                {label: 'pathName', sortText: '000'},
                {label: 'param', sortText: '100'}
            ], '$pa');
            assertEquals('$pathName,$param', captions(list), 'Dollar-prefixed matching is preserved');
            assert(list.filtered[0].vplDollarPrefix, 'Dollar-prefix insertion flag is preserved');
            list.setFilter('$pat');
            assertEquals('$pathName', captions(list), 'Dollar-prefixed incremental filtering');
            editor.completer.detach();
            list = await complete([
                {label: 'print', insertText: 'print(${1:value})', insertTextFormat: 2}
            ], 'pri');
            assertEquals('print(${1:value})', list.filtered[0].value, 'Snippets survive provider recreation');
            const standard = new aceModule.FilteredList([
                {caption: 'fooPrint', value: 'fooPrint', score: 100},
                {caption: 'print', value: 'print', score: 1}
            ]);
            standard.setFilter('pri');
            assertEquals('print,fooPrint', captions(standard), 'Global Ace filtering is not modified');
            editor.completers = [{
                getCompletions: function(editor, session, pos, prefix, callback) {
                    callback(null, [
                        {caption: 'fooPrint', value: 'fooPrint', score: 100},
                        {caption: 'print', value: 'print', score: 1}
                    ]);
                }
            }];
            list = await complete([], 'pri');
            assertEquals('print,fooPrint', captions(list), 'Non-LSP completers retain Ace filtering');
            const providerFactory = editor.completer.getCompletionProvider;
            client.aceCompleterAdapter.configure(editor, aceModule);
            assertEquals(providerFactory, editor.completer.getCompletionProvider,
                'Repeated setup does not wrap the provider again');
        } finally {
            editor.completer.destroy();
        }
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
async function runTests() {
    for (var i = 0; i < tests.length; i++) {
        testing = tests[i].name;
        suiteAssertsAtStart = nAsserts;
        var errorsAtStart = result.length;
        var startedAt = Date.now();
        var suite = $('<li></li>').attr('data-status', 'running').text('Running: ' + testing);
        $('#vpl_js_suites').append(suite);
        $('#vpl_js_progress').text('Running suite ' + (i + 1) + '/' + tests.length + ': ' + testing);
        try {
            await tests[i].test();
        } catch (e) {
            recordFailure('Exception: ' + String(e), e);
        }
        await new Promise(resolve => schedule(resolve, 0));
        var passed = result.length === errorsAtStart;
        suite.attr('data-status', passed ? 'passed' : 'failed')
            .text((passed ? 'Passed: ' : 'Failed: ') + testing + ' (' + (nAsserts - suiteAssertsAtStart) +
                ' assertions, ' + (Date.now() - startedAt) + ' ms)');
    }
}

export const start = async() => {
    result = [];
    testing = '';
    nAsserts = 0;
    finished = false;
    $('#test_results').empty().attr('data-status', 'running')
        .append($('<p id="vpl_js_summary" role="status"></p>').text('JavaScript tests are running.'))
        .append($('<p id="vpl_js_progress"></p>'))
        .append($('<ol id="vpl_js_suites"></ol>'))
        .append($('<h3></h3>').text('Failure details'))
        .append($('<ol id="vpl_js_failures"></ol>'));
    // Keep monitoring after completion because browser error events can arrive late.
    window.addEventListener('error', onError);
    window.addEventListener('unhandledrejection', onRejection);
    await runTests();
    finished = true;
    showResults();
};
