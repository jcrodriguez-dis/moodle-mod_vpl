#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Python language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using Thonny to debug python using the first file
# load common script and check programs
. common_script.sh
check_program thonny
if [ "$1" == "version" ] ; then
	get_program_version unknown
fi
get_first_source_file py
cat common_script.sh > vpl_wexecution
echo "thonny \"$FIRST_SOURCE_FILE\"" >>vpl_wexecution
chmod +x vpl_wexecution
