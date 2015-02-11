#!/bin/bash
# Default VHDL language run script for VPL
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program gvhdl
get_source_files vhdl vhd
#compile
gvhdl -a $SOURCE_FILES
NAME=${VPL_SUBFILE0%.*}
gvhdl -e $NAME
if [ -f $NAME ] ; then
	mv $NAME vpl_execution
else
	echo "============================================"
	echo "The first file name is the entity name"
	echo "Use lowercase in file names"
fi
