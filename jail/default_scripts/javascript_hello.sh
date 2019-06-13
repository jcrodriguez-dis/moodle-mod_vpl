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
		console.log('Hello from the JavaScript language!');
	}
};
END_OF_FILE

export VPL_SUBFILE0="vpl hello.js"
export VPL_SUBFILE1="javascript test/message.js"
