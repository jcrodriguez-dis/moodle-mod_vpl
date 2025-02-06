#!/bin/bash
# This file is part of VPL for Moodle
# Lisp (clisp) language hello source code
# Copyright 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# Note: clisp command line does NOT support spaces in the file name

cat >"vpl_hello.lisp" <<'END_OF_FILE'
(load "~/lisp test/message.lisp")
(hello)
END_OF_FILE

mkdir "lisp test" 2> /dev/null

cat >"lisp test/message.lisp" <<'END_OF_FILE'
(defun hello ()
	(format t (read-line))
)
END_OF_FILE
export VPL_SUBFILE0="vpl_hello.lisp"
export VPL_SUBFILE1="lisp test/message.lisp"
export INPUT_TEXT="Hello from the LISP language!"
