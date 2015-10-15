#!/bin/bash
# Default Python language run script for VPL
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program python
cat common_script.sh > vpl_execution
echo "python $VPL_SUBFILE0" >>vpl_execution
chmod +x vpl_execution
grep -E "^from[\t ]*Tkinter[\t ]*import" $VPL_SUBFILE0 2>&1 >/dev/null
if [ "$?" -eq "0" ]	; then
	mv vpl_execution vpl_wexecution
fi
