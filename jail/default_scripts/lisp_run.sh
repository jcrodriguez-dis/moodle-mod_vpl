#!/bin/bash
#Lisp run script for vpl
#Athors: 
#   Juan Vega Rodríguez; github: jdvr
#
. common_script.sh
check_program clisp
cat common_script.sh > vpl_execution
echo "clisp $VPL_SUBFILE0" >> vpl_execution
chmod +x vpl_execution
