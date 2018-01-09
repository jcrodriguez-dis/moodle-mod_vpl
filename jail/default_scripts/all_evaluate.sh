#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for getting information of the jail server and available interpreters and compilers
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Get the version of the compilers/interpreters available

cp common_script.sh common_script.sav
cat common_script.sh > all_execute
echo "echo \"<|--\"" >> all_execute
echo "echo \"-System information\"" >> all_execute
echo "cat /proc/version" >> all_execute
echo "grep MemTotal /proc/meminfo" >> all_execute
echo "cat /proc/partitions" >> all_execute
NG=0
NNG=0
NEG=0
FILES=*_run.sh
rm vpl_evaluate.cpp
for RUNSCRIPT in $FILES
do
	typeset -u LANGUAGE=$(echo "$RUNSCRIPT" | sed -r "s/_run.sh$//")
	LANGEXE=$(echo "$RUNSCRIPT" | sed -r "s/_run.sh$/_execute.sh/")
	if [ "$LANGUAGE" == "VPL" -o "$LANGUAGE" == "ALL" -o "$LANGUAGE" == "DEFAULT" ] ; then
		continue
	fi
	./$RUNSCRIPT version
	if [ -f vpl_execution ] ; then
		let "NG=NG+1"
		mv vpl_execution $LANGEXE
		echo "echo \"-$LANGUAGE\"" >> all_execute
		echo "./$LANGEXE" >> all_execute
	elif [ -f vpl_execution ] ; then
		let "NNG=NNG+1"
		echo "Error: generating $LANGUAGE compiler/interpreter version"
	fi
done
echo "echo \"--|>\"" >> all_execute
mv all_execute vpl_execution
chmod +x vpl_execution
