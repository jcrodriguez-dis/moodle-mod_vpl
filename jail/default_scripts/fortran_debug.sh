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
if [ "$1" == "version" ] ; then
	get_program_version --version
fi
get_source_files f f77
# Generate file with source files
generate_file_of_files .vpl_source_files
# Compile
gfortran -o program -g -O0 @.vpl_source_files
rm .vpl_source_files
if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
