#!/bin/bash
# $Id: default_evaluate.sh,v 1.5 2012-07-25 19:02:21 juanca Exp $
# Default evaluate script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load VPL environment vars
. common_script.sh
#exist run script?
if [ ! -s vpl_run.sh ] ; then
	echo "I'm sorry, but I haven't a default action to evaluate the type of submitted files"
else
	#avoid conflict with C++ compilation
	mv vpl_evaluate.cpp vpl_evaluate.cpp.save
	#Prepare run
	./vpl_run.sh >>vpl_compilation_error.txt 2>&1 
	if [ -f vpl_execution ] ; then
		mv vpl_execution vpl_test
		if [ -s vpl_evaluate.cases ] ; then
			mv vpl_evaluate.cases evaluate.cases
		else
			echo "Error need file 'vpl_evaluate.cases' to make an evaluation"
			exit 1
		fi
		#Add constants to vpl_evaluate.cpp
		if [ "$VPL_GRADEMIN" = "" ] ; then
			export VPL_GRADEMIN=10
			export VPL_GRADEMAX=0
		fi
		echo "const float VPL_GRADEMIN=$VPL_GRADEMIN;" >vpl_evaluate.cpp
		echo "const float VPL_GRADEMAX=$VPL_GRADEMAX;" >>vpl_evaluate.cpp
		let VPL_MAXTIME=VPL_MAXTIME-$SECONDS-1;
		echo "const int VPL_MAXTIME=$VPL_MAXTIME;" >>vpl_evaluate.cpp
		cat vpl_evaluate.cpp.save >> vpl_evaluate.cpp
		check_program g++
		g++ vpl_evaluate.cpp -g -lm -lutil -o vpl_execution
		if [ ! -f vpl_execution ] ; then
			echo "Error compiling evaluation program"
		fi
	else
		echo "#!/bin/bash" >> vpl_execution
		echo "echo" >> vpl_execution
		echo "echo 'Comment :=>>-$VPL_COMPILATIONFAILED'" >> vpl_execution
		echo "echo '<|--'" >> vpl_execution
		echo "cat vpl_compilation_error.txt" >> vpl_execution
		echo "echo '--|>'" >> vpl_execution		
		echo "echo 'Grade :=>>$VPL_GRADEMIN'" >> vpl_execution
		chmod +x vpl_execution
	fi
fi

