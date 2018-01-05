#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C# language
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using mcs
# load common script and check programs
. common_script.sh
check_program mcs
check_program mono
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "mcs --version" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
get_source_files cs
# compile
export MONO_ENV_OPTIONS=--gc=sgen
mcs -pkg:dotnet -out:output.exe -lib:/usr/lib/mono $SOURCE_FILES
if [ -f output.exe ] ; then
	cat common_script.sh > vpl_execution
	echo "export MONO_ENV_OPTIONS=--gc=sgen" >> vpl_execution
	echo "mono output.exe \$@" >> vpl_execution
	chmod +x vpl_execution
	grep -E "System\.Windows\.Forms" output.exe &>/dev/null
	if [ "$?" -eq "0" ]	; then
		mv vpl_execution vpl_wexecution
	fi
fi
