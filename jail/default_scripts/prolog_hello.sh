#!/bin/bash
# This file is part of VPL for Moodle
# Prolog language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "vpl hello.pro" <<'END_OF_FILE'
vpl_hello:- consult('test prolog/message.pro'), hello.
END_OF_FILE
mkdir "test prolog" 2> /dev/null
cat > "test prolog/message.pro" <<'END_OF_FILE'
hello:- read_line_to_string(user_input, Text), writeln(Text),halt.
END_OF_FILE

export VPL_SUBFILE0="vpl hello.pro"
export VPL_SUBFILE1="test prolog/message.pro"
export INPUT_TEXT="Hello from the Prolog language!"
