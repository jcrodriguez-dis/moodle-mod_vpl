#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Haskell language
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using "runhugs +98" with the first file
# load common script and check programs
. common_script.sh
check_program hugs
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "echo | hugs | head -n6" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
cat common_script.sh > vpl_execution
echo "runhugs +98 $VPL_SUBFILE0 \$@" >>vpl_execution
chmod +x vpl_execution
