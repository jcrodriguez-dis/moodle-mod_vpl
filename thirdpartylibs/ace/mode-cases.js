/**
 * @package mod_vpl. Ace highlighter for cases definition
 * @copyright 2021 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

ace.define("ace/mode/cases_highlight_rules", [ "require", "exports", "module", "ace/lib/oop", "ace/mode/text_highlight_rules" ],
        function(require, exports, module) {
            "use strict";
            var regexParameter = function(parameter) {
                return "^[ \\t]*(" + parameter + ")[ \\t]*=[ \\t]*";
            };
            var regexParameters = function(parameters) {
                return "^[ \\t]*(" + parameters.join("|") + ")[ \\t]*=[ \\t]*";
            };
            var regexPlaceHolders = function(placeHolders) {
                var fullPlaceHolders = placeHolders.map(name => "<<<" + name + ">>>");
                return "(" + fullPlaceHolders.join("|") + ")";
            };
            var lineParameters = [
                "case",
                "program to run",
                "program arguments",
                "expected exit code",
                "variation",
                "fail mark",
                "pass mark",
                "error mark",
                "timeout mark",
            ];
            var lineParametersWithPlaceHolders = [
                "case title format",
            ];

            var multiLineParameters = [
                "input",
            ];

            var multiLineParametersWithPlaceHolders = [
                "output",
                "fail message",
                "fail output message",
                "pass message",
                "timeout message",
                "fail exit code message",
                "final report message",
            ];
            var placeHolders = [
                "case_id",
                "case_title",
                "test_result_mark",
                "fail_mark",
                "pass_mark",
                "error_mark",
                "timeout_mark",
                "input",
                "input_inline",
                "check_type",
                "expected_output",
                "expected_output_inline",
                "program_output",
                "program_output_inline",
                "expected_exit_code",
                "exit_code",
                "time_limit",
                "num_tests",
                "num_tests_run",
                "num_tests_failed",
                "num_tests_passed",
                "num_tests_timeout",
                "num_tests_error",
                "grade_reduction",
            ];
            var oop = require("../lib/oop");
            var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;
            var lineTextValue = [{
                    token: "string",
                    regex: ".*$",
                    next: "start",
                },
            ];

            var lineTextValueWithPlaceHolders = [
                {
                    token: "string",
                    regex: "[^<]+",
                },
                {
                    token: "constant",
                    regex: regexPlaceHolders(placeHolders),
                },
                {
                    token: "string",
                    regex: "<",
                },
            ];

            var exitCodeValue = [
                {
                    token: "constant.numeric",
                    regex: "[ \\t]*-?[0-9]+[ \\t]*$",
                    next: "start",
                },
                {
                    token: "invalid.illegal",
                    regex: ".*$",
                    next: "start",
                },
            ];
            var gradeReductionValue = [
                {
                    token: "constant.numeric",
                    regex: "[ \\t]*[0-9]+\\.?[0-9]*%?[ \\t]*$",
                    next: "start",
                },
                {
                    token: "invalid.illegal",
                    regex: ".*$",
                    next: "start",
                },
            ];
            var floatValue = [
                {
                    token: "constant.numeric",
                    regex: "[ \\t]*[0-9]+(:?\\.[0-9]+)?[ \\t]*$",
                    next: "start",
                },
                {
                    token: "invalid.illegal",
                    regex: ".*$",
                    next: "start",
                },
            ];
            var lineEndParameter = [
                {
                    token: "string",
                    onMatch: function(value, currentState, stack) {
                        var result = /[ \\t]*([0-9a-zA-Z_]+)[ \\t]*$/.exec(value);
                        stack.push("preHereDoc", result[1])
                        return "string";
                    },
                    regex: "[ \\t]*([0-9a-zA-Z_]+)[ \\t]*$",
                    next: "preHereDoc",
                },
                {
                    token: "invalid.illegal",
                    regex: ".*$",
                    next: "start",
                },
            ]
            var comment = {
                    caseInsensitive: true,
                    token: "comment",
                    regex: "^[ \\t]*#.*$",
                    next: "start",
            };
            var base = [
                {
                    caseInsensitive: true,
                    token: "keyword",
                    regex: regexParameter("case"),
                    next: lineTextValue,
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameter("multiline end"),
                    next: lineEndParameter,
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameter("grade reduction"),
                    next: gradeReductionValue,
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameter("expected exit code"),
                    next: exitCodeValue,
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameter("time limit"),
                    next: floatValue,
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameters(lineParameters),
                    next: lineTextValue,
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameters(lineParametersWithPlaceHolders),
                    next: "lineWithPlaceHolders",
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameters(multiLineParameters),
                    next: "multiLine",
                },
                {
                    caseInsensitive: true,
                    token: "variable",
                    regex: regexParameters(multiLineParametersWithPlaceHolders),
                    next: "multilineWithPlaceHolders",
                },
            ];

            var CasesHighlightRules = function() {
                var keywords = [].concat(
                    lineParameters,
                    lineParametersWithPlaceHolders,
                    multiLineParameters,
                    multiLineParametersWithPlaceHolders).map( name => name + " =");
                keywords.push("multiline end =");
                keywords.push("grade reduction =");
                keywords.push("expected exit code =");
                keywords.push("time limit =");
                keywords.push("case =");
                keywords.push("case title format =");
                keywords.push("variation =");
                keywords = keywords.concat(placeHolders.map(name => "<<<" + name + ">>>"));
                this.createKeywordMapper({keyword: keywords.join("|")}, "identifier", 0);
                this.$rules = {
                    "start": [
                        base,
                        comment,
                        {
                            token: "text",
                            regex: "[ \\t]*$",
                            next: "start",
                        },
                        {
                            token: "invalid.illegal",
                            regex: ".*$",
                            next: "start",
                        }
                    ],
                    "lineWithPlaceHolders": [
                        lineTextValueWithPlaceHolders,
                        {
                            token: "string",
                            regex: ".*$",
                            next: "start",
                        },
                    ],
                    "multilineWithPlaceHolders": [
                        base,
                        lineTextValueWithPlaceHolders,
                        {
                            token: "string",
                            regex: ".*$",
                            next: "multilineWithPlaceHolders",
                        },
                    ],
                    "multiLine": [
                        base,
                        {
                            token: "string",
                            regex: ".*$",
                        },
                    ],
                    "preHereDoc": [
                        {
                            caseInsensitive: true,
                            token: "variable",
                            regex: regexParameters(multiLineParameters),
                            next: "hereDoc",
                        },
                        {
                            caseInsensitive: true,
                            token: "variable",
                            regex: regexParameters(multiLineParametersWithPlaceHolders),
                            next: "hereDocWithPlaceHolders",
                        },
                        {
                            token: "text",
                            regex: "^[ \\t]*$",
                            next: "preHereDoc",
                        },
                        {
                            token: "invalid.illegal",
                            regex: ".*$",
                            next: "start",
                        },
                    ],
                    "hereDoc": [
                        {
                            onMatch:  function(value, currentState, stack) {
                                if (value === stack[1]) {
                                    stack.shift();
                                    stack.shift();
                                    this.next = "start"
                                    return "constant";
                                }
                                this.next = "hereDoc";
                                return "string";
                            },
                            regex: ".*$",
                            next: "start"
                        }
                    ],
                    "hereDocWithPlaceHolders": [
                        {
                            token: "string",
                            regex: "[^<]+$",
                            next: "start",
                            onMatch:  function(value, currentState, stack) {
                                if (value === stack[1]) {
                                    stack.shift();
                                    stack.shift();
                                    this.next = "start";
                                    return "constant";
                                }
                                this.next = "hereDocWithPlaceHolders";
                                return "string";
                            },
                        },
                        {
                            token: "string",
                            regex: "[^<]+",
                            next: "hereDocWithPlaceHolders",
                        },
                        {
                            token: "constant",
                            regex: regexPlaceHolders(placeHolders),
                            next: "hereDocWithPlaceHolders",
                        },
                        {
                            token: "string",
                            regex: "<",
                            next: "hereDocWithPlaceHolders",
                        },
                        {
                            token: "string",
                            regex: ".*$",
                            next: "hereDocWithPlaceHolders",
                        },
                    ]
                };
                this.normalizeRules();
            };

            oop.inherits(CasesHighlightRules, TextHighlightRules);

            exports.CasesHighlightRules = CasesHighlightRules;
        });

ace.define("ace/mode/folding/cases",
        ["require", "exports", "module", "ace/lib/oop",
          "ace/mode/folding/fold_mode", "ace/range"],
          function(require, exports, module) {
     var oop = require("../../lib/oop");
     var baseFoldMode = require("./fold_mode").FoldMode;
     var Range = require("../../range").Range;
     // eslint-disable-next-line no-empty-function
     var foldMode = function() {
     };
     exports.FoldMode = foldMode;
     oop.inherits(foldMode, baseFoldMode);
     (function() {
         var foldStart = /^[ \t]*case[ \t]*=/i;
         this.getFoldWidgetRange = function(session, foldStyle, row) {
            var line = session.getLine(row);
            var match = line.search(foldStart);
            if (match == -1) {
                return null;
            }
            var ini = line.length;
            var maxRow = session.getLength();
            var ln = row + 1;
            var lastLine = line;
            for (ln = row + 1; ln < maxRow; ln++) {
                line = session.getLine(ln);
                if (line.search(foldStart) != -1) {
                    return new Range(row, ini, ln - 1, lastLine.length);
                }
                lastLine = line;
            }
            if (lastLine == "") {
                return new Range(row, ini, maxRow - 2, session.getLine(maxRow - 2).length);
            } else {
                return new Range(row, ini, maxRow - 1, lastLine.length);
            }
        };
        this.getFoldWidget = function(session, foldStyle, row) {
            var line = session.getLine(row);
            var match = line.search(foldStart);
            if (match == -1) {
                return "";
            }
            return "start";
        };
    }).call(foldMode);
});

ace.define("ace/mode/cases", ["require", "exports", "module", "ace/lib/oop", "ace/mode/text", "ace/mode/cases_highlight_rules",
        "ace/mode/folding/cases"], function(require, exports, module) {
    "use strict";
    var oop = require("../lib/oop");
    var textMode = require("./text").Mode;
    var highlight = require("./cases_highlight_rules").CasesHighlightRules;
    var folding = require("ace/mode/folding/cases").FoldMode;
    var mode = function() {
        this.HighlightRules = highlight;
        this.foldingRules = folding;
    };
    oop.inherits(mode, textMode);
    (function() {
        this.$id = "ace/mode/cases";
    }).call(mode.prototype);
    exports.Mode = mode;
});
