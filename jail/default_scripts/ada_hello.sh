#!/bin/bash
# This file is part of VPL for Moodle
# Ada Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
mkdir "ada test" 2> /dev/null 
cat > "ada test/vpl_hello.adb" <<'END_OF_FILE'
with message;
use message;
procedure vpl_hello is
begin
    hello;
end vpl_hello;
END_OF_FILE

cat > "ada test/message.ads" <<'END_OF_FILE'
package message is
    procedure hello;
end message;
END_OF_FILE

cat > "ada test/message.adb" <<'END_OF_FILE'
package message is
    procedure hello;
end message;
END_OF_FILE

cat > "ada test/message.adb" <<'END_OF_FILE'
with ada.text_io;
use ada.text_io;
package body message is
    procedure hello is
    text : String (1 .. 256);
  	size : Natural;
    begin
    	get_line(text, size);
        put_line( text(1 .. size) );
    end hello;
end message;
END_OF_FILE

export VPL_SUBFILE0="ada test/vpl_hello.adb"
export INPUT_TEXT="Hello from the Ada language!"
