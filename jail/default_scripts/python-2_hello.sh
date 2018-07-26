#!/bin/bash
# This file is part of VPL for Moodle
# Python language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
cat >vpl_hello2.py <<'END_OF_FILE'
import Tkinter
import tkMessageBox
tkMessageBox.showinfo('VPL','Hello from the Python2 language!')
END_OF_FILE
else
cat >vpl_hello2.py <<'END_OF_FILE'
print ('Hello from the Python2 language!')
END_OF_FILE
fi
export VPL_SUBFILE0=vpl_hello2.py
