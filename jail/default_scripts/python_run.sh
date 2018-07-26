#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Python language
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default python with the first file
# load common script and check programs
. common_script.sh
check_program python
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "python --version" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_first_source_file py
cat common_script.sh > vpl_execution
echo "python $FIRST_SOURCE_FILE \$@" >>vpl_execution
chmod +x vpl_execution
grep -E "Tkinter" $FIRST_SOURCE_FILE &> /dev/null
if [ "$?" -eq "0" ]	; then
	mv vpl_execution vpl_wexecution
fi
