#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C language 
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
#load common script and check programs
. common_script.sh
check_program gcc
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "gcc --version | head -n2" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
get_source_files c
#compile
eval gcc --std=c11 -O2 -fno-diagnostics-color -o vpl_execution $SOURCE_FILES -lm -lutil
