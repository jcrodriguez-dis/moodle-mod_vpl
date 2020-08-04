#!/bin/bash
# This file is part of VPL for Moodle
# D language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test d" 2> /dev/null

cat > "test d/vpl hello.d" <<'END_OF_FILE'
module vpl_hello;
import message;
void main() {
    hello();
}
END_OF_FILE

cat > "test d/vpl message.d" <<'END_OF_FILE'
module message;
import std.stdio;
void hello() {
    write(stdin.readln());
}
END_OF_FILE

export VPL_SUBFILE0="test d/vpl hello.d"
export VPL_SUBFILE1="test d/vpl message.d"
export INPUT_TEXT="Hello from the D language!"
