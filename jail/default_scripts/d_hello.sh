#!/bin/bash
# This file is part of VPL for Moodle
# D language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.d <<'END_OF_FILE'
import std.stdio;
void main() {
    writeln("Hello from the D language!");
}
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.d
