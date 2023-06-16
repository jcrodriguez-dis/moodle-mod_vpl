#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for getting information of the jail server and available interpreters and compilers
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Get the version of each compiler/interpreter available in the execution server
COMMON_SCRIPT_SAVED=.common_script.sav
cp common_script.sh $COMMON_SCRIPT_SAVED
cat common_script.sh > all_execute
. common_script.sh
ERRORSREPORT=.vpl_error_report.txt
#Remove student files
for FILENAME in $VPL_SUBFILES
do
	rm "$FILENAME" &>/dev/null
done

touch $ERRORSREPORT
echo "echo \"<|--\"" >> all_execute
echo "echo \"-System information\"" >> all_execute
echo "cat /proc/version" >> all_execute
echo "grep MemTotal /proc/meminfo" >> all_execute
echo "cat /proc/partitions" >> all_execute
FILES="*_run.sh *_debug.sh"
rm vpl_evaluate.cpp
for RUNSCRIPT in $FILES
do
	typeset -u LANGUAGE=$(echo "$RUNSCRIPT" | sed -r "s/_run.sh$//" | sed -r "s/_debug.sh$/ debugger/")
	LANGEXE=$(echo "$RUNSCRIPT" | sed -r "s/_run.sh$/_execute.sh/" | sed -r "s/_debug.sh$/_dexecute.sh/")
	if [ "$LANGUAGE" == "VPL" -o "$LANGUAGE" == "ALL" -o "$LANGUAGE" == "DEFAULT" ] ; then
		continue
	fi
	if [ "$LANGUAGE" == "VPL DEBUGGER" -o "$LANGUAGE" == "ALL DEBUGGER" -o "$LANGUAGE" == "DEFAULT DEBUGGER" ] ; then
		continue
	fi
	./$RUNSCRIPT version >> $ERRORSREPORT
	if [ -f vpl_execution ] ; then
		mv vpl_execution $LANGEXE
		echo "cp $COMMON_SCRIPT_SAVED common_script.sh" >> all_execute
		echo "echo \"-$LANGUAGE\"" >> all_execute
		echo "./$LANGEXE" >> all_execute
	elif [ -f vpl_wexecution ] ; then
		echo "Error: generating $LANGUAGE compiler/interpreter version" >> $ERRORSREPORT
	fi
done
echo "echo \"--|>\"" >> all_execute
echo "cat $ERRORSREPORT" >> all_execute
mv all_execute vpl_execution
chmod +x vpl_execution
