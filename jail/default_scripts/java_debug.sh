#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Java language
# Copyright (C) 2011 onwards Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino


function getJavaLaunchOptions {
    export JAVA_VERSION=$(java -version 2>&1 | head -n 1 | awk -F[\".] '{print $2}')
    if [ "$JAVA_VERSION" -lt "25" ] ; then
    	export JAVA_SHORT_LAUNCH_OPTIONS=0
    else
    	export JAVA_SHORT_LAUNCH_OPTIONS=1
    fi
}

function getClassName {
    #replace / for . and remove .java extension
	local CLASSNAME
	CLASSNAME=$(echo "$1" |sed 's/\//\./g')
	#remove file extension .java
	CLASSNAME=$(basename "$CLASSNAME" .java)
	echo "$CLASSNAME"
}

function getClassFile {
	#remove file extension .java and add .class
	local CLASSNAME
	CLASSNAME=$(basename "$1" .java)
	local DIRNAME
	DIRNAME=$(dirname "$1")
	echo "$DIRNAME/$CLASSNAME.class"
}

function hasMain {
	local FILE
	local REGEX_HAS_MAIN
	local RESULT
	RESULT=1
	FILE=$(getClassFile "$1")
	REGEX_HAS_MAIN="^  public static void main\(java.lang.String\[\]\);$"
	if [ "$JAVA_SHORT_LAUNCH_OPTIONS" -ne "0" ] ; then
		REGEX_HAS_MAIN="$REGEX_HAS_MAIN|^  (public |protected |)(static |)void main\((java.lang.String(\[\]|\.{3})|)\);$"
	fi
	if [ -f "$FILE" ] ; then
    	[ "$VPL_DEBUG" != "" ] && javap "$FILE"
	    javap "$FILE" | grep -E "$REGEX_HAS_MAIN" &> /dev/null
		RESULT=$?
		[ "$VPL_DEBUG" != "" ] && echo "Result: $RESULT"
	fi
	return $RESULT
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
	elif [ "$PROGRAM" == "jdb" ] ; then
		get_program_version -version
	else
		get_program_version --version
	fi
fi
check_program javap

# check java version is 25 or higher
getJavaLaunchOptions
JAVA_LIB_BASE="/usr/share/java"
JUNIT4_CANDIDATES="junit4.jar junit.jar junit/junit.jar"
for JUNIT4 in $JUNIT4_CANDIDATES; do
	LIBRARY_PATH="$JAVA_LIB_BASE/$JUNIT4"
	if [ -f "$LIBRARY_PATH" ] ; then
		[ "$VPL_DEBUG" != "" ] && echo "JUnit4 found: $LIBRARY_PATH"
		CLASSPATH=$CLASSPATH:$LIBRARY_PATH
		break
	fi
done
HAMCREST_CANDIDATES="hamcrest-core.jar hamcrest.jar hamcrest/hamcrest.jar"
for HAMCREST in $HAMCREST_CANDIDATES; do
	LIBRARY_PATH="$JAVA_LIB_BASE/$HAMCREST"
	if [ -f "$LIBRARY_PATH" ] ; then
		[ "$VPL_DEBUG" != "" ] && echo "Hamcrest found: $LIBRARY_PATH"
		CLASSPATH=$CLASSPATH:$LIBRARY_PATH
		break
	fi
done
get_source_files jar NOERROR
for JARFILE in $SOURCE_FILES
do
	[ "$VPL_DEBUG" != "" ] && echo "Adding JAR to CLASSPATH: $JARFILE"
	CLASSPATH=$CLASSPATH:$JARFILE
done
export CLASSPATH

get_source_files java
# compile all .java files

javac -Xlint:deprecation $2 $SOURCE_FILES
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
# Search main procedure class
MAINCLASS=
for FILENAME in $VPL_SUBFILES
do
	if hasMain "$FILENAME"; then
		MAINCLASS=$(getClassName "$FILENAME")
		[ "$VPL_DEBUG" != "" ] && echo "Main class found in submitted file: $MAINCLASS"
		break
	fi
done
if [ "$MAINCLASS" = "" ] ; then
	for FILENAME in $SOURCE_FILES
	do
		if hasMain "$FILENAME"; then
			MAINCLASS=$(getClassName "$FILENAME")
			[ "$VPL_DEBUG" != "" ] && echo "Main class found in execution file: $MAINCLASS"
			break
		fi
	done
fi
# If not main procedure then search for junit4 test classes
if [ "$MAINCLASS" = "" ] ; then
	TESTCLASS=
	for FILENAME in $SOURCE_FILES
	do
		CLASSFILE=$(getClassFile "$FILENAME")
		if [ ! -f "$CLASSFILE" ] ; then
			continue
		fi
		[ "$VPL_DEBUG" != "" ] && echo "Checking for JUnit4 test class in: $CLASSFILE"
		if grep "org/junit/" "$CLASSFILE" &> /dev/null; then
			TESTCLASS=$(getClassName "$FILENAME")
			[ "$VPL_DEBUG" != "" ] && echo "JUnit4 test class found: $TESTCLASS"
			break
		fi
	done
	# If no main and no test class then stop
	if [ "$TESTCLASS" = "" ] ; then
		if [ "$JAVA_SHORT_LAUNCH_OPTIONS" -eq "0" ] ; then
			echo "Class with \"public static void main(String[] arg)\" method not found"
		else
			echo "Class or file with \"void main\" method not found"
		fi
		echo "or JUnit4 test class not found"
		echo "Please submit a Java class with a main method or a JUnit4 test class."
		exit 0
	fi
fi

if [ "$MAINCLASS" = "" ] ; then
	MAINCLASS=$TESTCLASS
fi
cat vpl_environment.sh > vpl_execution
echo "export CLASSPATH=$CLASSPATH:$HOME" >> vpl_execution
chmod +x vpl_execution
# is jgrasp installed ?
if [ "$(command -v jgrasp)" != "" ] ; then
	echo "jgrasp $MAINCLASS.java" >> vpl_execution
	echo "wait_end jgrasp" >> vpl_execution
	mv vpl_execution vpl_wexecution
elif [ "$(command -v ddd)" != "" ] ; then
	echo "ddd --jdb --debugger \"jdb\" $MAINCLASS" >> vpl_execution
	mkdir .ddd &>/dev/null
	mkdir .ddd/sessions &>/dev/null
	mkdir .ddd/themes &>/dev/null
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
	REGEX_IS_JAVAX="javax/swing/(JFrame|JDialog|JOptionPane|JApplet)"
	REGEX_IS_JAVAFX="javafx/application/(Application|Scene)"
	for FILENAME in $SOURCE_FILES
	do
		CLASSFILE=$(getClassFile "$FILENAME")
		if [ ! -f "$CLASSFILE" ] ; then
			continue
		fi
		if grep -E "($REGEX_IS_JAVAX|$REGEX_IS_JAVAFX)" $CLASSFILE &> /dev/null; then
			check_program x-terminal-emulator xterm
			cat vpl_environment.sh > vpl_wexecution
			chmod +x vpl_wexecution
			echo "./.vpl_javadebug" >> vpl_wexecution
			mv vpl_execution .vpl_javadebug
			break
		fi
	done
fi
