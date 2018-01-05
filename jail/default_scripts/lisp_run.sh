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
	echo "#!/bin/bash" > vpl_execution
	echo "clisp --version | head -n1" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
cat common_script.sh > vpl_execution
echo "clisp $VPL_SUBFILE0 \$@" >> vpl_execution
chmod +x vpl_execution
