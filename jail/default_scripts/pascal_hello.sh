#!/bin/bash
# This file is part of VPL for Moodle
# Pascal language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# Program name and unit name must be equals to the file name
cat >"vpl_hello.pas" <<'END_OF_FILE'
program vpl_hello;
uses message;
begin
   hello;
end.
END_OF_FILE

cat >"message.pas" <<'END_OF_FILE'
unit message;
interface
   procedure hello;
implementation
   procedure hello;
   var text: string;
   begin
      readln(text);
      writeln(text);
   end;
end.
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.pas
export VPL_SUBFILE1=message.pas
export INPUT_TEXT="Hello from the Pascal language!"