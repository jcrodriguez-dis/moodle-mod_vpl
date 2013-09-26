#!/bin/bash
# $Id: vhdl_run.sh,v 1.3 2012-07-25 19:02:19 juanca Exp $
# Default VHDL language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program ghdl
#compile
ghdl -a *.vhd*
echo "The first file/class must have the Main method"
NAME=${VPL_SUBFILE0%.*}
ghdl -e $NAME
if [ -f $NAME ] ; then
	mv $NAME vpl_execution
fi
