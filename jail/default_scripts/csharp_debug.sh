#!/bin/bash
# $Id: csharp_debug.sh,v 1.4 2012-09-24 15:13:22 juanca Exp $
# Default C# language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
export MONO_ENV_OPTIONS=--gc=sgen
export MONO_GC_PARAMS=max-heap-size=64m
check_program gmcs
check_program mdb
#compile
gmcs -out:output.exe *.cs
if [ -f output.exe ] ; then
	cat common_script.sh > vpl_execution
	echo "export MONO_ENV_OPTIONS=--gc=sgen" >> vpl_execution
	echo "export MONO_GC_PARAMS=max-heap-size=64m" >> vpl_execution
	echo "mdb output.exe" >> vpl_execution
	chmod +x vpl_execution
fi
