#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Verilog language
# Copyright (C) 2015 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using iverilog
# load common script and check programs
. common_script.sh
check_program iverilog
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "iverilog -V | head -n3" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_source_files v
#compile
iverilog -ovpl_execution $SOURCE_FILES
