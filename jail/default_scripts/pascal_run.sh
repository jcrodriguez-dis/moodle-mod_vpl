#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Pascal language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using FPC or gpc
# load common script and check programs
. common_script.sh
check_program fpc gpc
if [ "$1" == "version" ] ; then
	PROPATH=$(command -v fpc 2>/dev/null)
	if [ "$PROGRAM" == "gpc" ] ; then
		get_program_version --version
	else
		get_program_version -h
	fi
	exit 0;
fi 
get_source_files pas p
#compile with gpc or fpc
if [ "$PROGRAM" == "gpc" ] ; then
	# Generate file with source files
	generate_file_of_files .vpl_source_files
	# Compile
	gpc --automake -o vpl_execution @.vpl_source_files -lm &> .vpl_compilation_errors
	rm .vpl_source_files
else
	get_first_source_file pas pp p
	fpc -ovpl_execution "$FIRST_SOURCE_FILE" &> .vpl_compilation_errors
fi
if [ ! -f vpl_execution ] ; then
	cat .vpl_compilation_errors
fi

