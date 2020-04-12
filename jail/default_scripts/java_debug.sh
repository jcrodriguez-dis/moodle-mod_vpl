#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Java language
# Copyright (C) 2011 onwards Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

function getClassName {
    #replace / for .
	local CLASSNAME=$(echo "$1" |sed 's/\//\./g')
	#remove file extension .java
	CLASSNAME=$(basename "$CLASSNAME" .java)
	echo "$CLASSNAME"
}

# @vpl_script_description Using jdb or ddd if detected
# load common script and check programs
. common_script.sh
check_program javac
check_program java
check_program jgrasp ddd jdb
if [ "$1" == "version" ] ; then
	if [ "$PROGRAM" == "jgrasp" ] ; then
		get_program_version unknown
	else
		get_program_version --version
	fi
fi
JUNIT4=/usr/share/java/junit4.jar
if [ -f $JUNIT4 ] ; then
	CLASSPATH=$CLASSPATH:$JUNIT4
fi
get_source_files jar NOERROR
for JARFILE in $SOURCE_FILES
do
	CLASSPATH=$CLASSPATH:$JARFILE
done
export CLASSPATH
get_source_files java
#compile all SOURCE_FILES files
javac -g -Xlint:deprecation $SOURCE_FILES
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
#Search main procedure class
MAINCLASS=
for FILENAME in $VPL_SUBFILES
do
	egrep "void[ \n\t]+main[ \n\t]*\(" $FILENAME &>/dev/null
	if [ "$?" -eq "0" ]	; then
		MAINCLASS=$(getClassName "$FILENAME")
		break
	fi
done
if [ "$MAINCLASS" = "" ] ; then
	for FILENAME in $SOURCE_FILES
	do
	    echo $FILENAME
		egrep "void[ \n\t]+main[ \n\t]*\(" $FILENAME &>/dev/null
		if [ "$?" -eq "0" -a "$MAINCLASS" = "" ] ; then
			MAINCLASS=$(getClassName "$FILENAME")
			break
		fi
	done
fi
if [ "$MAINCLASS" = "" ] ; then
#Search for junit4 test classes
	TESTCLASS=
	for FILENAME in $SOURCE_FILES
	do
		grep "org\.junit\." $FILENAME &>/dev/null
		if [ "$?" -eq "0" ]	; then
			TESTCLASS=$(getClassName "$FILENAME")
			break
		fi
	done
	if [ "$TESTCLASS" = "" ] ; then
		echo "Class with \"public static void main(String[] arg)\" method not found"
		exit 0
	fi
fi
if [ "$MAINCLASS" = "" ] ; then
	MAINCLASS=$TESTCLASS
fi
cat common_script.sh > vpl_execution
echo "export CLASSPATH=$CLASSPATH:$HOME" >> vpl_execution
chmod +x vpl_execution
# is jgrasp installed ?
if [ "$(command -v jgrasp)" != "" ] ; then
	echo "jgrasp $MAINCLASS.java" >> vpl_execution
	echo "wait_end jgrasp" >> vpl_execution
	mv vpl_execution vpl_wexecution
elif [ "$(command -v ddd)" != "" ] ; then
	echo "ddd --jdb --debugger \"jdb\" $MAINCLASS" >> vpl_execution
	mkdir .ddd
	mkdir .ddd/sessions
	mkdir .ddd/themes
	cat >.ddd/init <<END_OF_FILE
Ddd*splashScreen: off
Ddd*startupTips: off
Ddd*suppressWarnings: on
Ddd*displayLineNumbers: on
Ddd*saveHistoryOnExit: off

! DO NOT ADD ANYTHING BELOW THIS LINE -- DDD WILL OVERWRITE IT
END_OF_FILE
	mv vpl_execution vpl_wexecution
else
	echo "jdb $MAINCLASS" >> vpl_execution
	for FILENAME in $SOURCE_FILES
	do
		grep -E "JFrame|JDialog" $FILENAME &>/dev/null
		if [ "$?" -eq "0" ]	; then
			check_program x-terminal-emulator xterm
			cat common_script.sh > vpl_wexecution
			chmod +x vpl_wexecution
			echo "./.vpl_javadebug" >> vpl_wexecution
			mv vpl_execution .vpl_javadebug
			break
		fi
	done
fi
