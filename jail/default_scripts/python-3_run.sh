#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Python language
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using python3 with the first file
# load common script and check programs
. common_script.sh
check_program python3
if [ "$1" == "version" ] ; then
	get_program_version --version
fi
get_first_source_file py
cat common_script.sh > vpl_execution
echo "export TERM=ansi" >>vpl_execution
echo "python3 \"$FIRST_SOURCE_FILE\" \$@" >>vpl_execution
chmod +x vpl_execution
get_source_files py
IFS=$'\n'
for file_name in $SOURCE_FILES
do
	grep -i "Tkinter" "$file_name" &> /dev/null
	if [ "$?" -eq "0" ]	; then
		mv vpl_execution vpl_wexecution
		break
	fi
done
IFS=$SIFS
