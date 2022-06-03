#!/bin/bash
# This file is part of VPL for Moodle
# Lua language Hello source code
# Copyright 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.lua" <<'END_OF_FILE'
message = require("lua test/vpl message")
message.hello()
END_OF_FILE

mkdir "lua test" 2> /dev/null

cat >"lua test/vpl message.lua" <<'END_OF_FILE'
local message = {}
function message.hello()
	print(io.read())
end

return message
END_OF_FILE

export VPL_SUBFILE0="vpl hello.lua"
export VPL_SUBFILE1="lua test/vpl message.lua"
export INPUT_TEXT="Hello from the Lua language!"