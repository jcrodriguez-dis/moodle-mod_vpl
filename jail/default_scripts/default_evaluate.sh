#!/bin/bash
# This file is part of VPL for Moodle
# Default evaluate script for VPL
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load VPL environment vars
. common_script.sh
if [ "$SECONDS" = "" ] ; then
	export SECONDS=20
fi
let VPL_MAXTIME=$SECONDS-5;
if [ "$VPL_GRADEMIN" = "" ] ; then
	export VPL_GRADEMIN=0
	export VPL_GRADEMAX=10
fi

#exist run script?
if [ ! -s vpl_run.sh ] ; then
	echo "I'm sorry, but I haven't a default action to evaluate the type of submitted files"
else
	#avoid conflict with C++ compilation
	mv vpl_evaluate.cpp vpl_evaluate.cpp.save
	#Prepare run
	./vpl_run.sh &>>vpl_compilation_error.txt
	cat vpl_compilation_error.txt
	if [ -f vpl_execution ] ; then
		mv vpl_execution vpl_test
		if [ -f vpl_evaluate.cases ] ; then
			mv vpl_evaluate.cases evaluate.cases
		else
			echo "Error need file 'vpl_evaluate.cases' to make an evaluation"
			exit 1
		fi
		mv vpl_evaluate.cpp.save vpl_evaluate.cpp
		check_program g++
		g++ vpl_evaluate.cpp -g -lm -lutil -o .vpl_tester
		if [ ! -f .vpl_tester ] ; then
			echo "Error compiling evaluation program"
			exit 1
		else
			cat vpl_environment.sh >> vpl_execution
			echo "./.vpl_tester" >> vpl_execution
		fi
	else
		echo "#!/bin/bash" >> vpl_execution
		echo "echo" >> vpl_execution
		echo "echo '<|--'" >> vpl_execution
		echo "echo '-$VPL_COMPILATIONFAILED'" >> vpl_execution
		if [ -f vpl_wexecution ] ; then
			echo "echo '======================'" >> vpl_execution
			echo "echo 'It seems you are trying to test a program with a graphic user interface'" >> vpl_execution
		fi
		echo "echo '--|>'" >> vpl_execution		
		echo "echo" >> vpl_execution		
		echo "echo 'Grade :=>>$VPL_GRADEMIN'" >> vpl_execution
	fi
	chmod +x vpl_execution
fi
