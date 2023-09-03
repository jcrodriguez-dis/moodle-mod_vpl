#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# PSeInt language hello source code https://pseint.sourceforge.net
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "vpl_hello.psc" <<'END_OF_FILE'
Proceso Hello
    Leer text
    Escribir text
FinProceso
END_OF_FILE
export VPL_SUBFILE0="vpl_hello.psc"
export INPUT_TEXT="Hello from PSeInt language!"
