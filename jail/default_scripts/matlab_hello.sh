#!/bin/bash
# This file is part of VPL for Moodle
# Matlab/Octave language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.m" <<'END_OF_FILE'
source "matlab test/vpl message.m"
hello();

END_OF_FILE

mkdir "matlab test" 2> /dev/null

if [ "$1" == "gui" ] ; then
cat >"matlab test/vpl message.m" <<'END_OF_FILE'
function hello ()
	x = -10:0.1:10;
	plot(x,cos(x));
	title("VPL running Matlab/Octave");
	figure;
endfunction
END_OF_FILE

cat >> "vpl hello.m" <<'END_OF_FILE'
input("Continue");
exit();
END_OF_FILE

else

cat >"matlab test/vpl message.m" <<'END_OF_FILE'
function hello ()
    fprintf("%s\n", fgetl(stdin()));
endfunction
END_OF_FILE
fi
export VPL_SUBFILE0="vpl hello.m"
export INPUT_TEXT="Hello from Matlab/Octave!"