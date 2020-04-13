#!/bin/bash
# Default JavaScript language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Juan Carlos Rodriguez-del-pino
#load common script and check programs

cat >"vpl hello.js" <<'END_OF_FILE'
message = require('./javascript test/message.js');
message.hello();
END_OF_FILE

mkdir "javascript test" 2>/dev/null

cat >"javascript test/message.js" <<'END_OF_FILE'
module.exports = { hello:
	function () {
		const readlineModule = require('readline');
		const readline = readlineModule.createInterface({
		    input: process.stdin
        });
		readline.question('', function(text) {
			console.log(text);
			readline.close();
		});
	}
};
END_OF_FILE

export VPL_SUBFILE0="vpl hello.js"
export VPL_SUBFILE1="javascript test/message.js"
export INPUT_TEXT="Hello from the JavaScript language!"