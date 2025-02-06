#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C++ language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#@vpl_script_description Using default g++ with math and util libs
#load common script and check programs
. common_script.sh
check_program g++
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 
get_source_files cpp C
# Generate file with source files
generate_file_of_files .vpl_source_files
# Compile
g++ -fno-diagnostics-color -o vpl_execution $2 @.vpl_source_files -lm -lutil

#Added by Tamar
if [ -f "Teacher/program.cpp.ta" ] ; then
	mv Teacher/program.cpp.ta program.cpp
	g++ -g -o vpl_test_teacher program.cpp
	chmod +x vpl_test_teacher
fi

rm .vpl_source_files
