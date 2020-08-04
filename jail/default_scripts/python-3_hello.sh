#!/bin/bash
# This file is part of VPL for Moodle
# Python language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
	Tk=$(python3 -c 'import pkgutil; print(1 if pkgutil.find_loader("tkinter") else 0)')
	if [ "$Tk" == "1" ] ; then
cat > "vpl hello3.py" <<'END_OF_FILE'
import message3
message3.hello()
END_OF_FILE
cat > "message3.py" <<'END_OF_FILE'
from tkinter import messagebox
def hello():
	messagebox.showinfo('VPL','Hello from the Python3 language!')
END_OF_FILE
export VPL_SUBFILE0="vpl hello3.py"
export VPL_SUBFILE1="message3.py"
	fi
else
cat > "vpl hello3.py" <<'END_OF_FILE'
import message3
message3.hello()
END_OF_FILE
cat > "message3.py" <<'END_OF_FILE'
def hello():
	print(input())
END_OF_FILE
export VPL_SUBFILE0="vpl hello3.py"
export VPL_SUBFILE1="message3.py"
export INPUT_TEXT="Hello from the Python3 language!"
fi

