#!/bin/bash
# Default D language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

#load common script and check programs
. common_script.sh
check_program gdc
get_source_files d
#compile
gdc -o vpl_execution -lm -lutil $SOURCE_FILES
