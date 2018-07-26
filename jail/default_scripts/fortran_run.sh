#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Fortran language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using gfortran
# load VPL environment vars
. common_script.sh
check_program gfortran
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "gfortran --version | head -n2" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
#compile
get_source_files f f77
gfortran -o vpl_execution $SOURCE_FILES
