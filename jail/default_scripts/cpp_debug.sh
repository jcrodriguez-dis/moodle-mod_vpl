#!/bin/bash
# $Id: cpp_debug.sh,v 1.4 2012-09-24 15:13:21 juanca Exp $
# Default C++ language debug script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program g++
check_program gdb
get_source_files cpp C
#compile
g++ -o program -g -O0 -lm -lutil $SOURCE_FILES
if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
