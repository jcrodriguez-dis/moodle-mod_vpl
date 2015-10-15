#!/bin/bash
# Default JavaScript language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Juan Carlos Rodriguez-del-pino
#load common script and check programs

. common_script.sh
check_program nodejs
cat common_script.sh > vpl_execution
echo "nodejs $VPL_SUBFILE0" >> vpl_execution
chmod +x vpl_execution
