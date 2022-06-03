#!/bin/bash
# This file is part of VPL for Moodle
# Minizinc Hello source code
# Copyright 2021 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "minizinc test" 2> /dev/null
cat >"minizinc test/minizinc message.mzn" <<'END_OF_FILE'
var 1..5: x;
solve maximize x;
output [ "Hello from Minizinc constraint modeling language" ];
END_OF_FILE
export VPL_SUBFILE0="minizinc test/minizinc message.mzn"
