#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running GUI Hello program from all languages
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
cp common_script.sh common_script.sav
cat common_script.sh > all_execute
NG=0
NNG=0
NEG=
FILES=*_hello.sh
for HELLOSCRIPT in $FILES
do
	cp common_script.sav common_script.sh
	typeset -u LANGUAGE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$//")
	RUNSCRIPT=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_run.sh/")
	VPLEXE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_execute.sh/")
	echo "Generating Hello for $LANGUAGE"
	. $HELLOSCRIPT gui
	echo "export VPL_SUBFILE0=$VPL_SUBFILE0" >> common_script.sh
	echo "export SOURCE_FILES=$VPL_SUBFILE0" >> common_script.sh
	. $RUNSCRIPT batch
	if [ -f vpl_wexecution ] ; then
		let "NG=NG+1"
		mv vpl_wexecution $VPLEXE
		echo "echo \"Launching $LANGUAGE\"" >> all_execute
		echo "/bin/bash ./$VPLEXE" >> all_execute
	elif [ -f vpl_execution ] ; then
		rm vpl_execution
		let "NNG=NNG+1"
		echo "Default script for $LANGUAGE does not generated GUI program"
	else
		let "NEG=NEG+1"
		echo "Error: generating GUI Hello for $LANGUAGE not generated"
	fi
done
echo "read" >> all_execute
chmod +x all_execute
cat common_script.sh > vpl_wexecution
echo "xterm -e ./all_execute" >> vpl_wexecution
chmod +x vpl_wexecution
