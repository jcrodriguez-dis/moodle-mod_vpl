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
 * MarkDown for the VPL IDE
 *
 * @copyright 2026 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

import {marked} from 'marked';
import {VPLUtil} from './vplutil';

const parserOptions = {
    gfm: true,
    breaks: false,
};

/**
 * Return the position for of the closed Bracket corresponding to an open Bracket at a given position
 * @param {String} text Text to search the closed Bracket in
 * @param {Number} from position of the open Bracket in the text
 * @returns {Number} position of the closed Bracket
 */
function posClosedBracket(text, from) {
    let openBrackets = 0;
    for (let i = from; i < text.length; i++) {
        if (text[i] === "[") {
            openBrackets++;
        } else if (text[i] === "]") {
            openBrackets--;
            if (openBrackets <= 0) {
                if (text.length > i + 1 && text[i + 1] === "(") {
                    return i;
                } else {
                    return -1;
                }
            }
        }
    }
    return -1;
}

/**
 * Fix the markdown link with better processing the link text accepting [] and ` inside and links text.
 * @param {String} markdown Full markdown string to fix
 * @param {Number} pos Position in the markdown string to start fixing
 * @param {Number} limit Position in the markdown string to stop fixing
 * @param {Boolean} insideCodeBlock Whether the current position is inside a code block
 * @return {Object} Fixed markdown string
 */
function fixMarkDownLink(markdown, pos, limit, insideCodeBlock) {
    let fixed = "";
    while (pos < limit) {
        let nextLink = markdown.indexOf("[", pos);
        if (nextLink === -1 || nextLink >= limit) {
            fixed += fixMarkDownCodeInline(markdown, pos, limit, insideCodeBlock);
            break;
        }
        fixed += fixMarkDownCodeInline(markdown, pos, nextLink, insideCodeBlock);
        let endLink = posClosedBracket(markdown, nextLink);
        if (endLink === -1 || endLink >= limit) {
            fixed += fixMarkDownCodeInline(markdown, nextLink, limit, insideCodeBlock);
            break;
        } else {
            // URL title
            fixed += fixMarkDownCodeInline(markdown, nextLink + 1, endLink, insideCodeBlock);
            let outLink = markdown.indexOf(")", endLink + 1);
            if (outLink === -1 || outLink >= limit) {
                fixed += fixMarkDownCodeInline(markdown, endLink + 1, limit, insideCodeBlock);
                break;
            } else {
                // Remove URL
                pos = outLink + 1;
                continue;
            }
        }
    }
    return fixed;
}

/**
 * Fix inline code marks `
 * @param {String} markdown Full markdown string to fix
 * @param {Number} pos Position in the markdown string to start fixing
 * @param {Number} limit Position in the markdown string to stop fixing
 * @param {Boolean} insideCodeBlock Whether the current position is inside a code block
 * @return {Object} Fixed markdown string
 */
function fixMarkDownCodeInline(markdown, pos, limit, insideCodeBlock) {
    let fixed = "";
    let openCode = insideCodeBlock ? "´" : "<code>";
    let closeCode = insideCodeBlock ? "´" : "</code>";
    while (pos < limit) {
        let nextCode = markdown.indexOf("`", pos);
        if (nextCode === -1 || nextCode >= limit) {
            fixed += markdown.substring(pos, limit);
            break;
        } else {
            fixed += markdown.substring(pos, nextCode) + openCode;
            nextCode++;
            let endCode = markdown.indexOf("`", nextCode);
            if (endCode === -1 || endCode >= limit) {
                fixed += markdown.substring(nextCode, limit);
                break;
            } else {
                fixed += markdown.substring(nextCode, endCode) + closeCode;
                pos = endCode + 1;
            }
        }
    }
    return fixed;
}

/**
 * It filters the given markdown
 *  1. Removing MD links
 *  2. Adding <code> tags for `code inline`
 * @param {String} markdown The markdown string to filter
 * @return {String} The filtered markdown string without MD links
 */
function fixMarkDown(markdown) {
    var pos = 0;
    var fixed = "";
    while (pos < markdown.length) {
        let startCodeBlock = markdown.indexOf("```", pos);
        if (startCodeBlock === -1) {
            // No more code blocks, fix the rest of the markdown and break the loop
            fixed += fixMarkDownLink(markdown, pos, markdown.length, false);
            break;
        } else {
            // Fix the markdown until the start of code block
            fixed += fixMarkDownLink(markdown, pos, startCodeBlock, false);
        }
        fixed += "```";
        startCodeBlock += 3;
        let endCodeBlock = markdown.indexOf("```", startCodeBlock);
        if (endCodeBlock === -1) {
            // No closing code block, fix the rest of the markdown and break the loop
            fixed += "```" + fixMarkDownLink(markdown, startCodeBlock, markdown.length, true);
            break;
        } else {
            // Fix the markdown inside the code block
            fixed += fixMarkDownLink(markdown, startCodeBlock, endCodeBlock, true) + "```";
            pos = endCodeBlock + 3;
        }
    }
    var lines = fixed.split(/\r\n|\n|\r/);
    fixed = dedentListBlocks(lines).join("\n") + "\n";
    return fixed;
}

/**
 * Markdown treats lines indented 4+ spaces as a code block (pre + code).
 * @param {Array<String>} lines Lines of the markdown text
 * @return {Array<String>} Lines with list blocks dedented
 */
function dedentListBlocks(lines) {
    var listMarker = /^( *)(?:[-*+]|\d+[.)])\s/;
    var result = [];
    var i = 0;
    while (i < lines.length) {
        if (lines[i].trim() === "") {
            result.push(lines[i]);
            i++;
            continue;
        }
        // Collect a block of consecutive non-blank lines.
        var block = [];
        while (i < lines.length && lines[i].trim() !== "") {
            block.push(lines[i]);
            i++;
        }
        var first = block[0].match(listMarker);
        if (first && first[1].length > 0) {
            var indent = first[1].length;
            for (var j = 0; j < block.length; j++) {
                // Remove up to "indent" leading spaces, keeping relative indentation of nested items.
                var lead = block[j].match(/^ */)[0].length;
                block[j] = block[j].substring(Math.min(indent, lead));
            }
        }
        for (var k = 0; k < block.length; k++) {
            result.push(block[k]);
        }
    }
    return result;
}

/**
 * Convert markdown syntax to Html
 * @param {String} markdown Text with markdown syntax
 * @returns {String} New string with HTML syntax
 */
const markDownToHTML = function(markdown) {
    if (typeof markdown !== 'string') {
        return "";
    }
    let filteredMarkdown = fixMarkDown(markdown);
    let rawHTML = marked.parse(filteredMarkdown, parserOptions);
    let html = VPLUtil.sanitizeHTML(rawHTML);
    return html;
};

const init = function() {
    // No initialization needed for now
};

export const VPLMD = {
    markDownToHTML: markDownToHTML,
    init: init
};
