#!/bin/bash
# This file is part of VPL for Moodle
# Scheme language hello source code
# Copyright 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# include do not acept spaces in file or directory name

cat > "vpl hello.scm" <<'END_OF_FILE'
(include "testscheme/vplmessage.scm")
(hello)
(exit)
END_OF_FILE

mkdir "testscheme" 2> /dev/null
cat > "testscheme/vplmessage.scm" <<'END_OF_FILE'
(define (hello)
    (display (read-line))
    (newline)
)
END_OF_FILE

export VPL_SUBFILE0="vpl hello.scm"
export VPL_SUBFILE1="testscheme/vplmessage.scm"
export INPUT_TEXT="Hello from the Scheme language!"
