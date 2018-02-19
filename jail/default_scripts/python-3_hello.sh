#!/bin/bash
# This file is part of VPL for Moodle
# Python language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
	Tk=$(python3 -c 'import pkgutil; print(1 if pkgutil.find_loader("Tkinter") else 0)')
	if [ "$Tk" == "1" ] ; then
cat >vpl_hello3.py <<'END_OF_FILE'
import Tkinter
import tkMessageBox
tkMessageBox.showinfo('VPL','Hello from the Python3 language!')
END_OF_FILE
	fi
else
cat >vpl_hello3.py <<'END_OF_FILE'
print ('Hello from the Python3 language!')
END_OF_FILE
fi
export VPL_SUBFILE0=vpl_hello3.py

