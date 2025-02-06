#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running R language
# Copyright (C) 2017 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default R
# load common script and check programs
. common_script.sh
check_program R
if [ "$1" == "version" ] ; then
	get_program_version --version 3
fi

check_program Rscript
#Select first file
get_first_source_file r R
#compile
cat common_script.sh > vpl_wexecution
cat  "$FIRST_SOURCE_FILE" >> .Rprofile
check_program x-terminal-emulator xterm
if [ "$1" == "batch" ] ; then
	echo "$PROGRAM -e R --vanilla -f \"$FIRST_SOURCE_FILE\"" >>vpl_wexecution
else
	echo "$PROGRAM -e R -q" >>vpl_wexecution
fi
echo "wait_end R" >>vpl_wexecution
chmod +x vpl_wexecution
