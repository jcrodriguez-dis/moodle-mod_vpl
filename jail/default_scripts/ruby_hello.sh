#!/bin/bash
# This file is part of VPL for Moodle
# Ruby language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "vpl hello.rb" <<'END_OF_FILE'
require "~/test ruby/vpl message"
hello
END_OF_FILE

mkdir "test ruby" 2> /dev/null
cat > "test ruby/vpl message.rb" <<'END_OF_FILE'
def hello
    text = $stdin.read
    print text
end
END_OF_FILE

export VPL_SUBFILE0="vpl hello.rb"
export VPL_SUBFILE1="test ruby/vpl message.rb"
export INPUT_TEXT="Hello from the Ruby language!"