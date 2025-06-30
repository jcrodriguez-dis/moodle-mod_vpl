#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for preparing for running programs written in Cobol language
# Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#@vpl_script_description Using gnucobol cobc
#load common script and check programs

VPL_ERROR_FILE=.vpl_execution.error
VPL_SOURCE_FILES=.vpl_source_files
# Function to clean fortify source messages
function clean_fortify_source {
	grep _FORTIFY_SOURCE $VPL_ERROR_FILE &> /dev/null
	if [ "$?" == "0" ] ; then
		head -n -2 $VPL_ERROR_FILE
	else
		cat $VPL_ERROR_FILE
	fi
}

. common_script.sh
check_program cobc
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 
get_source_files cbl cob
# Generate file with source files
generate_file_of_files $VPL_SOURCE_FILES NOQUOTE
# Compile

VPL_SIFS=$IFS
IFS=$'\n'
for VPL_file_name in $SOURCE_FILES ; do
	cobc -c $2 "$VPL_file_name" &> $VPL_ERROR_FILE
	[ "$?" != "0" ] && VPL_COMPILATION_ERROR=1
	clean_fortify_source
done
IFS=$VPL_SIFS
cobc -x -o vpl_execution $2 $(cat $VPL_SOURCE_FILES) &> $VPL_ERROR_FILE
if [ "$?" != "0" -a "$VPL_COMPILATION_ERROR" == "" ] ; then
	clean_fortify_source
fi
rm -f $VPL_ERROR_FILE
rm -f $VPL_SOURCE_FILES
apply_run_mode
