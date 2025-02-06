#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C# language
# Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using csc or mcs
# load common script and check programs
. common_script.sh
check_program mono
check_program csc mcs
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 
[ "$PROGRAM" == "mcs" ] && export PKGDOTNET="-pkg:dotnet"
get_source_files cs
OUTPUTFILE=output.exe
# Generate file with source files
generate_file_of_files .vpl_source_files
# Detect NUnit
NUNITLIBFILE=$(ls /usr/lib/cli/nunit.framework*/nunit.framework.dll | tail -n 1)
[ -f "$NUNITLIBFILE" ] && export NUNITLIB="-r:$NUNITLIBFILE"
# Compile
export MONO_ENV_OPTIONS=--gc=sgen
EXECUTABLE=false
$PROGRAM $PKGDOTNET $NUNITLIB -out:$OUTPUTFILE -lib:/usr/lib/mono @.vpl_source_files &>.vpl_compilation_message
if [ -f $OUTPUTFILE ] ; then
	EXECUTABLE=true
else
	# Try to compile as dll
	OUTPUTFILE=output.dll
	if [ "$NUNITLIB" != "" ] ; then
		PROGRAM $PKGDOTNET $NUNITLIB -out:$OUTPUTFILE -target:library -lib:/usr/lib/mono @.vpl_source_files &> /dev/null
	fi
fi
rm .vpl_source_files
if [ -f $OUTPUTFILE ] ; then
	cat common_script.sh > vpl_execution
	chmod +x vpl_execution
	echo "export MONO_ENV_OPTIONS=--gc=sgen" >> vpl_execution
	# Detect NUnit
	grep -E "nunit\.framework" $OUTPUTFILE &>/dev/null
	if [ "$?" -eq "0" ]	; then
		echo "nunit-console -nologo $OUTPUTFILE" >> vpl_execution
	fi
	if [ "$EXECUTABLE" == "true" ] ; then
		echo "mono $OUTPUTFILE \$@" >> vpl_execution
		grep -E "System\.Windows\.Forms" $OUTPUTFILE &>/dev/null
		if [ "$?" -eq "0" ]	; then
			mv vpl_execution vpl_wexecution
		fi
	fi
else
	cat .vpl_compilation_message
fi
