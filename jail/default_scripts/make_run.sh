#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for compiling and running programs using make
# Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using make to compile programs
# load common script and check programs
. common_script.sh

check_program make
if [ "$1" == "version" ] ; then
    get_program_version --version
fi
if [ -f makefile ] ; then
	MAKEFILE=makefile
else
	if [ -f Makefile ] ; then
		MAKEFILE=Makefile
	else
		echo "👉 Error: No Makefile found"
		exit
	fi
fi
TARGETLINE==$(grep -o -E "^[ \\t]*TARGET[ \\t]*=[ \\t]*[^ \\t]+" $MAKEFILE)
TARGETLINE==$(echo $TARGETLINE | tail -n 1)
if [[ $TARGETLINE =~ (TARGET[[:space:]]*=[[:space:]]*([[:alnum:]]+)) ]] ; then
	TARGET=${BASH_REMATCH[2]}
else
	echo "👉 Error trying to get the make target name"
	echo "  Define the target using \"TARGET = target_name\""
	exit
fi
make -s &> .vpl_error
if [ "$?" != "0" ] ; then
	echo "👉 Error making '$TARGET'"
	cat .vpl_error
	exit
fi
if [  ! -f "$TARGET" ] ; then
	echo "👉 Error: file '$TARGET' not found after correct make"
	cat .vpl_error
	exit
fi

mv "$TARGET" vpl_execution
