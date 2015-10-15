#!/bin/bash
# Default LUA language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

#load common script and check programs

. common_script.sh
check_program lua
cat common_script.sh > vpl_execution
echo "lua $VPL_SUBFILE0" >>vpl_execution
chmod +x vpl_execution
