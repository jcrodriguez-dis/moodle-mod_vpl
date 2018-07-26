#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running GUI Hello programs of available languages
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Run a GUI "hello world" program of all programming languages available

cp common_script.sh common_script.sav
cat common_script.sh > all_execute
NG=0
NNG=0
NEG=0
LANGGEN=""
LANGNGEN=""
LANGEG=""
FILES=*_hello.sh
SFDIR=/tmp/.saved$USERID
mkdir $SFDIR
touch .guierrors
for HELLOSCRIPT in $FILES
do
	typeset -u LANGUAGE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$//")
	RUNSCRIPT=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_run.sh/")
	DEBUGSCRIPT=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_debug.sh/")
	VPLEXE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_execute.sh/")
	VPLDEBEXE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_debexecute.sh/")
	echo -n "$LANGUAGE:"
	rm .curerror &>/dev/null
	. $HELLOSCRIPT gui &>.curerror
	if [ "$VPL_SUBFILE0" == "" ] ; then
		continue
	fi
	cp common_script.sav common_script.sh
	echo "export VPL_SUBFILE0=$VPL_SUBFILE0" >> common_script.sh
	echo "export SOURCE_FILE0=$VPL_SUBFILE0" >> common_script.sh
	echo "export VPL_SUBFILES=$VPL_SUBFILE0" >> common_script.sh
	eval ./$RUNSCRIPT batch &>>.curerror
	if [ -f vpl_wexecution ] ; then
		if [ -f "$VPL_SUBFILE0" ] ; then
			mv $VPL_SUBFILE0 $SFDIR
		fi
		let "NG=NG+1"
		LANGGEN="$LANGGEN $LANGUAGE"
		mv vpl_wexecution $VPLEXE
		echo -n " Compiled for run with GUI"
		echo "echo \"Launching $LANGUAGE\"" >> all_execute
		echo "/bin/bash ./$VPLEXE" >> all_execute
		echo "if [ -f $VPL_SUBFILE0 ] ; then" >> all_execute
		echo "rm $VPL_SUBFILE0" >> all_execute
		echo "fi" >> all_execute
	elif [ -f vpl_execution ] ; then
		echo -n " Compiled for run with TUI => removed"
		rm vpl_execution
		let "NNG=NNG+1"
		LANGNGEN="$LANGNGEN $LANGUAGE"
	else
		echo -n " Not compiled"
		let "NEG=NEG+1"
		LANGEG="$LANGEG $LANGUAGE"
	fi
    if [ -f ./$DEBUGSCRIPT ] ; then
    	cp $RUNSCRIPT vpl_run.sh
		eval ./$DEBUGSCRIPT batch &>>.curerror
		if [ -f vpl_wexecution ] ; then
			let "NG=NG+1"
			LANGGEN="$LANGGEN $LANGUAGE"
			mv vpl_wexecution $VPLDEBEXE
			echo -n " Compiled for debug with GUI"
			echo "echo \"Launching debuger for $LANGUAGE\"" >> all_execute
			echo "/bin/bash ./$VPLDEBEXE" >> all_execute
		fi
    fi
    echo
	if [ -s .curerror ] ; then
		echo "- The compilation of $LANGUAGE has generated the folloging menssages:" >> .tuierrors
		cat .curerror >> .guierrors
	fi
done
echo "echo \"Finsh. Press enter\"" >> all_execute
echo "read" >> all_execute
chmod +x all_execute
mv $SFDIR/* . &>/dev/null
rmdir $SFDIR
cat common_script.sh > vpl_wexecution
echo "x-terminal-emulator -e ./all_execute" >> vpl_wexecution
chmod +x vpl_wexecution
if [ "$LANGGEN" != "" ] ; then
	echo "Generated GUI program(s) for $NG language(s): $LANGGEN"
fi
if [ "$LANGGEN" != "" ] ; then
	echo "Not generated GUI program(s) for $NNG language(s): $LANGNGEN"
fi
if [ "$LANGEG" != "" ] ; then
	echo "Error generating GUI program(s) for $NEG language(s): $LANGEG"
fi
cat .guierrors
