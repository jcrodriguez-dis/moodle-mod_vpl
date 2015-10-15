#!/bin/bash
# $Id: ada_debug.sh,v 1.4 2012-09-24 15:13:21 juanca Exp $
# Default ADA language debug script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common scriVPL environment vars
. common_script.sh
check_program gnat
check_program gdb
#compile
gnat make -gnat05 -gnatW8 -q -g -o program $VPL_SUBFILE0
if [ -f program ] ; then
	cat common_script.sh > vpl_execution
	echo "gdb program" >> vpl_execution
	chmod +x vpl_execution
fi
