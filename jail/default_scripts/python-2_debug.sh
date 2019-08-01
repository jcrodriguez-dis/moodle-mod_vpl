#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Python language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using python2 PUDB with the first file
# load common script and check programs
. common_script.sh
if [ "$1" == "version" ] ; then
	exit
fi
check_program python2

# Detect if PuDB is installed
PUDB=$($PROGRAM -c 'import pudb; print(1)' 2>/dev/null)
if [ "$PUDB" == "1" ] ; then
	MOD=pudb
else
	MOD=pdb
fi

get_first_source_file py
cat common_script.sh > vpl_execution
echo "TERM=ansi" >>vpl_execution
echo "$PROGRAM -m $MOD \"$FIRST_SOURCE_FILE\"" >>vpl_execution
chmod +x vpl_execution
if [ "$PUDB" == "1" ] ; then
	mv vpl_execution py2_debug_execution
	cat common_script.sh > vpl_wexecution
	check_program x-terminal-emulator xterm
	echo "$PROGRAM -e ./py2_debug_execution" >> vpl_wexecution
	echo "wait_end py2_debug_execution" >> vpl_wexecution
	chmod +x vpl_wexecution
	chmod +x py2_debug_execution
fi
