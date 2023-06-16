#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C language 
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using GCC with math and util libs
# load common script and check programs
. common_script.sh
check_program gcc
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 
get_source_files c
# Generate file with source files
generate_file_of_files .vpl_source_files
# Compile
gcc -fno-diagnostics-color -o vpl_execution $2 @.vpl_source_files -lm -lutil 
rm .vpl_source_files
