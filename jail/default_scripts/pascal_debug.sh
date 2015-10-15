#!/bin/bash
# $Id: pascal_debug.sh,v 1.6 2012-09-24 15:13:22 juanca Exp $
# Default Pascal language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program gdb
get_source_files pas p
#compile
PROPATH=$(command -v fpc 2>/dev/null)
if [ "$PROPATH" == "" ] ; then
	PROPATH=$(command -v gpc 2>/dev/null)
	if [ "$PROPATH" == "" ] ; then
		echo "The jail need to install "GNU Pascal" or "Fre PAscal" to debug this type of program"
		exit 0;
	else
		gpc -g -O0 -o program $SOURCE_FILES
	fi
else
	fpc -g -oprogram $VPL_SUBFILE0
fi

if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
