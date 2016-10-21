#!/bin/bash
# This file is part of VPL for Moodle
# Fortran language hello source code
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.f <<'END_OF_FILE'
       PRINT *, "Hello from the Fortran language!"
       END
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.f
