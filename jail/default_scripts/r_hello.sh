#!/bin/bash
# This file is part of VPL for Moodle
# R language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "vpl hello.r" <<'END_OF_FILE'
source("test R/message.R")
hello()
q()
END_OF_FILE

mkdir "test R" 2> /dev/null

cat >"test R/message.R" <<'END_OF_FILE'
require(tcltk)
hello <- function() {
msgBox <- tkmessageBox(title = "VPL",
                       message = "Hello from the R language!", icon = "info", type = "ok")
}

END_OF_FILE
export VPL_SUBFILE0="vpl hello.r"
