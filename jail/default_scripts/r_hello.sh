#!/bin/bash
# This file is part of VPL for Moodle
# R language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.r <<'END_OF_FILE'
require(tcltk)
msgBox <- tkmessageBox(title = "VPL",
                       message = "Hello from the R language!", icon = "info", type = "ok")
q();
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.r
