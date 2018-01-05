#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for JavaScript language using NodeJs
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using nodejs with the first file
# load common script and check programs
. common_script.sh
check_program nodejs
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "nodejs -v" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
cat common_script.sh > vpl_execution
echo "nodejs $VPL_SUBFILE0 \$@" >> vpl_execution
chmod +x vpl_execution
