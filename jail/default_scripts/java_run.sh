#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Java language
# Copyright (C) 2015 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

# @vpl_script_description Using default javac, run JUnit if detected
# load common script and check programs
source common_script.sh

check_program javac
if [ "$1" == "version" ] ; then
	get_program_version -version
fi
[ "$VPL_DEBUG" != "" ] && javac -version

check_program java
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

cat vpl_environment.sh > vpl_execution
echo "export CLASSPATH=$CLASSPATH" >> vpl_execution

if [ ! "$MAINCLASS" = "" ] ; then
	echo "java -enableassertions $MAINCLASS \$@" >> vpl_execution
else
	echo "java org.junit.runner.JUnitCore $TESTCLASS \$@" >> vpl_execution
fi
chmod +x vpl_execution
REGEX_IS_JAVAX="javax/swing/(JFrame|JDialog|JOptionPane|JApplet)"
REGEX_IS_JAVAFX="javafx/application/(Application|Scene)"
for FILENAME in $SOURCE_FILES
do
	CLASSFILE=$(getClassFile "$FILENAME")
	if [ ! -f "$CLASSFILE" ] ; then
		continue
	fi
	if grep -E "($REGEX_IS_JAVAX|$REGEX_IS_JAVAFX)" $CLASSFILE &> /dev/null; then
		mv vpl_execution vpl_wexecution
		break
	fi
done
apply_run_mode
