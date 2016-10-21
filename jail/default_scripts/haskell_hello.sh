#!/bin/bash
# This file is part of VPL for Moodle
# Haskell language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.hs <<'END_OF_FILE'
main = putStrLn "Hello from the Haskell language!"
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.hs
