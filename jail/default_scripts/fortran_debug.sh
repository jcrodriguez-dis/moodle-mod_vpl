#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Fortran language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using gfortran and gdb
# load common script and check programs
. common_script.sh
check_program gfortran
check_program gdb
get_source_files f f77
# compile
gfortran -o program -g -O0 $SOURCE_FILES
if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
