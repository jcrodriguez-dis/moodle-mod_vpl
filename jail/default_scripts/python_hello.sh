#!/bin/bash
# This file is part of VPL for Moodle
# Python language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

function generateHelloPython2() {
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
	print(raw_input())
END_OF_FILE
	fi
	export VPL_SUBFILE0="vpl hello.py"
	export VPL_SUBFILE1="message.py"
	export INPUT_TEXT="Hello from the Python language!"
}
function generateHelloPython3() {
	if [ "$1" == "gui" ] ; then
		Tk=$(python3 -c 'import pkgutil; print(1 if pkgutil.find_loader("tkinter") else 0)')
		if [ "$Tk" == "1" ] ; then
			cat > "vpl hello.py" <<'END_OF_FILE'
import message
message.hello()
END_OF_FILE
			cat > "message.py" <<'END_OF_FILE'
from tkinter import messagebox
def hello():
	messagebox.showinfo('VPL','Hello from the Python language!')
END_OF_FILE
			export VPL_SUBFILE0="vpl hello.py"
			export VPL_SUBFILE1="message.py"
		fi
	else
		cat > "vpl hello.py" <<'END_OF_FILE'
import message
message.hello()
END_OF_FILE
		cat > "message.py" <<'END_OF_FILE'
def hello():
	print(input())
END_OF_FILE
		export VPL_SUBFILE0="vpl hello.py"
		export VPL_SUBFILE1="message.py"
		export INPUT_TEXT="Hello from the Python language!"
	fi
}

check_program python3 python python2
PY2=$($PROGRAM --version | grep "Python 2")
if [ "$PY2" == "" ] ; then
	generateHelloPython3 $1
else
	generateHelloPython2 $1
fi
