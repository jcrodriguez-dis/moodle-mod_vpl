#!/bin/bash
# This file is part of VPL for Moodle
# Default evaluate script for VPL
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# Load VPL environment vars.
. common_script.sh
if [ "$VPL_MAXTIME" = "" ] ; then
	export VPL_MAXTIME=20
fi
let VPL_MAXTIME=$VPL_MAXTIME+5;
if [ "$VPL_GRADEMIN" = "" ] ; then
	export VPL_GRADEMIN=0
	export VPL_GRADEMAX=10
fi

# Does exist the run script?
if [ ! -s vpl_run.sh ] ; then
	echo "I apologize, but I do not find a default action to run the submitted file types."
else
	# Prepare the run script/program (vpl_execution)
	./vpl_run.sh &>>vpl_compilation_error.txt
	cat vpl_compilation_error.txt
	if [ -f vpl_execution ] ; then
		mv vpl_execution vpl_test
		if [ -f vpl_evaluate.cases ] ; then
			mv vpl_evaluate.cases evaluate.cases
		else
			echo "Error: I need the file 'vpl_evaluate.cases' to do the evaluation."
			exit 1
		fi
		check_program g++
		if [ "$VPL_DEBUG" != "" ] ; then
			DEBUGMODE="-g -DDEBUG"
		fi
		g++ .giotes/giotes.cpp -o .vpl_tester -lm -lutil $DEBUGMODE
		if [ ! -f .vpl_tester ] ; then
			echo "Error compiling evaluation program"
			exit 1
		else
			cat vpl_environment.sh >> vpl_execution
			echo "./.vpl_tester" >> vpl_execution
		fi
	else
		(
			cat vpl_environment.sh
			echo
			echo "echo"
			echo "echo '<|--'"
			echo 'echo "-$VPL_COMPILATIONFAILED"'
			if [ -f vpl_wexecution ] ; then
				echo "echo '======================'"
				echo "echo 'It seems you are trying to test a program with a graphic user interface.'"
			fi
			echo "echo '--|>'"
			echo "echo"
			echo "echo 'Grade :=>>$VPL_GRADEMIN'"
		) > vpl_execution
	fi
	chmod +x vpl_execution
fi
