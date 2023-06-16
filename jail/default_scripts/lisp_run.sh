#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Lisp Language
#Athors: 
#   Juan Vega Rodríguez; github: jdvr
#

# @vpl_script_description Using clisp with the first file
. common_script.sh
check_program clisp
if [ "$1" == "version" ] ; then
	get_program_version --version 1
fi
get_first_source_file lisp lsp
cat common_script.sh > vpl_execution
echo "clisp \"$FIRST_SOURCE_FILE\" \$@" >> vpl_execution
chmod +x vpl_execution
