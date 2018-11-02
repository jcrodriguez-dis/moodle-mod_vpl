#!/bin/bash
# This file is part of VPL for Moodle
# Python language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "vpl hello.py" <<'END_OF_FILE'
import message
message.hello()
END_OF_FILE

if [ "$1" == "gui" ] ; then
cat > "message.py" <<'END_OF_FILE'
import Tkinter
import tkMessageBox
def hello():
	tkMessageBox.showinfo('VPL','Hello from the Python language!')
END_OF_FILE
else
cat > "message.py" <<'END_OF_FILE'
def hello():
	print('Hello from the Python language!')
END_OF_FILE
fi

export VPL_SUBFILE0="vpl hello.py"
export VPL_SUBFILE1="message.py"
