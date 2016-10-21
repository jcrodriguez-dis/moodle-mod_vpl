#!/bin/bash
# This file is part of VPL for Moodle
# Ada Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.adb <<'END_OF_FILE'
with ada.text_io;
use ada.text_io;
procedure vpl_hello is
begin
    put_line("Hello from the Ada language!");
end vpl_hello;
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.adb
