#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Go language
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

# @vpl_script_description Using "go build -o" with first file
# load common script and check programs

. common_script.sh
check_program go
if [ "$1" == "version" ] ; then
	get_program_version version
fi
export GOPATH=~/
mkdir bin &> /dev/null
mkdir pkg &> /dev/null

get_first_source_file go
go build -o go_program "$FIRST_SOURCE_FILE"
cat common_script.sh > vpl_execution
echo "export GOPATH=~/" >>vpl_execution
echo "./go_program" >>vpl_execution
chmod +x vpl_execution
