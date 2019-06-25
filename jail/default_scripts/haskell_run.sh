#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Haskell language
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using "ghc or runhugs +98" with the first file
# load common script and check programs
. common_script.sh
check_program ghc hugs
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	if [ "$PROGRAM" == "hugs" ] ; then
		echo "echo | $PROGRAMPATH | head -n6" >> vpl_execution
	else
		echo "$PROGRAMPATH --version" >> vpl_execution
	fi
	chmod +x vpl_execution
	exit
fi
get_first_source_file hs lhs
if [ "$PROGRAM" == "hugs" ] ; then
	cat common_script.sh > vpl_execution
	echo "runhugs +98 \"$FIRST_SOURCE_FILE\" \$@" >>vpl_execution
	chmod +x vpl_execution
else
	$PROGRAMPATH -v0 -o vpl_execution "$FIRST_SOURCE_FILE"
fi
