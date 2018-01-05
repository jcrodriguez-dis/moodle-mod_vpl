#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Pascal language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using FPC or gpc
# load common script and check programs
. common_script.sh
if [ "$1" == "version" ] ; then
	PROPATH=$(command -v fpc 2>/dev/null)
	if [ "$PROPATH" == "" ] ; then
		PROPATH=$(command -v gpc 2>/dev/null)
		if [ "$PROPATH" == "" ] ; then
			echo "The jail need to install "GNU Pascal" or "Free Pascal" to run this type of program"
		else
			echo "#!/bin/bash" > vpl_execution
			echo "gpc --version" >> vpl_execution
			chmod +x vpl_execution
		fi
	else
		echo "#!/bin/bash" > vpl_execution
		echo "fpc -h | head -n2" >> vpl_execution
		chmod +x vpl_execution
	fi
	exit 0;
fi 
get_source_files pas p
#compile with gpc or fpc
PROPATH=$(command -v fpc 2>/dev/null)
if [ "$PROPATH" == "" ] ; then
	PROPATH=$(command -v gpc 2>/dev/null)
	if [ "$PROPATH" == "" ] ; then
		echo "The jail need to install "GNU Pascal" or "Free Pascal" to run this type of program"
		exit 0;
	else
		gpc --automake -o vpl_execution $SOURCE_FILES -lm 
	fi
else
	fpc -ovpl_execution $SOURCE_FILES
fi

