#!/bin/bash
# This file is part of VPL for Moodle
# Pascal language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.pas <<'END_OF_FILE'
program vpl_hello;
begin
  writeln('Hello from the Pascal language!')
end.
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.pas
