#!/bin/bash
# Default D language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

# @vpl_script_description Using gdc with math and util libs
# load common script and check programs
. common_script.sh
check_program gdc
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 
get_source_files d
# Generate file with source files
generate_file_of_files .vpl_source_files
# Compile
gdc -o vpl_execution -lm -lutil @.vpl_source_files
rm .vpl_source_files
