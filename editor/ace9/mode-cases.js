/**
 * @package mod_vpl. Ace highlighter for cases definition
 * @copyright 2013 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

ace.define("ace/mode/cases_highlight_rules", [ "require", "exports", "module", "ace/lib/oop", "ace/mode/text_highlight_rules" ],
        function(require, exports, module) {
            "use strict";
            var rkey = function(key) {
                return "^[ \\t]*" + key + "[ \\t]*=";
            }
            var oop = require("../lib/oop");
            var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;
            var endText = [ {
                token : "constant.string",
                regex : ".*$",
                next : "start"
            } ];
            var endNumber = [ {
                token : "constant.numeric",
                regex : "[ \\t]*[0-9]+[ \\t]*$",
                next : "start"
            }, {
                token : "text",
                regex : ".*$",
                next : "start"
            } ];
            var endNumberPercent = [ {
                token : "constant.numeric",
                regex : "[ \\t]*[0-9]+\\.?[0-9]*%?[ \\t]*$",
                next : "start"
            }, {
                token : "text",
                regex : ".*$",
                next : "start"
            } ];
            var base = [ {
                caseInsensitive : true,
                token : "keyword",
                regex : '^[ \\t]*case[ \\t]*=$',
                next : 'start'
            },{
                caseInsensitive : true,
                token : "keyword",
                regex : rkey("case"),
                next : endText
            }, {
                caseInsensitive : true,
                token : "variable",
                regex : rkey("fail message|program to run|program arguments|variation"),
                next : endText
            }, {
                caseInsensitive : true,
                token : "variable",
                regex : rkey("(grade reduction)"),
                next : endNumberPercent
            }, {
                caseInsensitive : true,
                token : "variable",
                regex : rkey("(expected exit code)"),
                next : endNumber
            }, {
                caseInsensitive : true,
                token : "variable",
                regex : rkey("(input|output)"),
                next : "endBlock"
            }, ];

            var CasesHighlightRules = function() {
                this.createKeywordMapper({
                    keyword:"|Variation =|Program arguments =" +
                    		"|Program to run =|Expected exit code =" +
                    		"|Fail message =|Grade reduction =" +
                    		"|Output =|Input =|Case ="},
                        "identifier", 0);
                this.$rules = {
                    "start" : [ base, {
                        token : "text",
                        regex : ".*$",
                        next : "start"
                    } ],
                    "endBlock" : [ base, {
                        token : "string",
                        regex : ".*$"
                    } ]
                };
                this.normalizeRules();
            };

            oop.inherits(CasesHighlightRules, TextHighlightRules);

            exports.CasesHighlightRules = CasesHighlightRules;
        });

ace.define("ace/mode/folding/cases",
        [ "require", "exports", "module", "ace/lib/oop",
          "ace/mode/folding/fold_mode", "ace/range" ],
          function(require, exports, module) {
     var oop = require("../../lib/oop");
     var baseFoldMode = require("./fold_mode").FoldMode;
     var Range = require("../../range").Range;
     var foldMode = function() {
     };
     exports.FoldMode = foldMode;
     oop.inherits(foldMode, baseFoldMode);
     (function() {
         var foldStart = /^[ \\t]*case[ \\t]*=/i;
         this.getFoldWidgetRange = function(session, foldStyle, row) {
            var line = session.getLine(row), match = line.search(foldStart);
            if (match == -1 )
                return;
            var ini = line.length;
            var maxRow = session.getLength();
            var ln = row+1;
            var lastLine = line;
            for (var ln = row+1; ln < maxRow ; ln++ ) {
                line = session.getLine(ln);
                if( line.search(foldStart) != -1) {
                    return new Range(row, ini, ln - 1, lastLine.length);                
                }
                lastLine = line;
            }
            if ( lastLine == "") {
                return new Range(row, ini, maxRow-2, session.getLine(maxRow-2).length);
            } else {
                return new Range(row, ini, maxRow-1, lastLine.length);
            }
        };
        this.getFoldWidget = function(session, foldStyle, row) {
            var line = session.getLine(row), match = line.search(foldStart);
            if (match == -1 )
                return "";
            return "start";                
        };
    }).call(foldMode);
});

ace.define("ace/mode/cases", [ "require", "exports", "module", "ace/lib/oop", "ace/mode/text", "ace/mode/cases_highlight_rules",
        "ace/mode/folding/cases" ], function(require, exports, module) {
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
        this.$id = "ace/mode/cases"
    }).call(mode.prototype);
    exports.Mode = mode;
});
