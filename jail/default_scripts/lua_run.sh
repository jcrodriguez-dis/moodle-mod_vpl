#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running LUA language
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

# @vpl_script_description Using lua with the first file
# load common script and check programs

. common_script.sh
check_program lua
if [ "$1" == "version" ] ; then
	get_program_version -v
fi
get_first_source_file lua
cat common_script.sh > vpl_execution
echo "lua \"$FIRST_SOURCE_FILE\" \$@" >>vpl_execution
chmod +x vpl_execution
