#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging ADA language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using GDB and GNAT with Ada 2012 features 
# load common script and check programs and VPL environment vars
. common_script.sh
check_program gnat
check_program gdb
get_first_source_file adb

# compile
gnat make -gnat12 -gnatW8 -q -g -o program "$FIRST_SOURCE_FILE"
if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
