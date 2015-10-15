#!/bin/bash
# $Id: csharp_run.sh,v 1.4 2012-09-24 15:13:21 juanca Exp $
# Default C# language run script for VPL
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program gmcs
check_program mono
get_source_files cs
#compile
export MONO_ENV_OPTIONS=--gc=sgen
export MONO_GC_PARAMS=max-heap-size=64m
gmcs -pkg:dotnet -out:output.exe $SOURCE_FILES
if [ -f output.exe ] ; then
	cat common_script.sh > vpl_execution
	echo "export MONO_ENV_OPTIONS=--gc=sgen" >> vpl_execution
	echo "export MONO_GC_PARAMS=max-heap-size=64m" >> vpl_execution
	echo "mono output.exe" >> vpl_execution
	chmod +x vpl_execution
	for FILENAME in $SOURCE_FILES
	do
		grep -E "System\.Windows\.Forms" $FILENAME 2>&1 >/dev/null
		if [ "$?" -eq "0" ]	; then
			mv vpl_execution vpl_wexecution
			break
		fi
	done
fi
