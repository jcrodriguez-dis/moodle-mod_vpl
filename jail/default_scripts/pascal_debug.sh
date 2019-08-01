#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Pascal language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using gdb
# load common script and check programs
. common_script.sh
check_program gdb
if [ "$1" == "version" ] ; then
	get_program_version --version
fi
get_source_files pas p
#compile
PROPATH=$(command -v fpc 2>/dev/null)
if [ "$PROPATH" == "" ] ; then
	PROPATH=$(command -v gpc 2>/dev/null)
	if [ "$PROPATH" == "" ] ; then
		echo "The jail need to install "GNU Pascal" or "Free Pascal" to debug this type of program"
		exit 0;
	else
		# Generate file with source files
		generate_file_of_files .vpl_source_files
		# Compile
		gpc -g -O0 -o program @.vpl_source_files -lm
		rm .vpl_source_files
	fi
else
	get_first_source_file pas p
	fpc -g -oprogram "$FIRST_SOURCE_FILE"
fi

if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
