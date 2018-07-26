#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Java language
# Copyright (C) 2015 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

function getClassName {
    #replace / for .
	local CLASSNAME=$(echo "$1" |sed 's/\//\./g')
	#remove file extension .java
	CLASSNAME=$(basename "$CLASSNAME" .java)
	echo $CLASSNAME
}
function getClassFile {
	#remove file extension .java
	local CLASSNAME=$(basename "$1" .java)
	local DIRNAME=$(dirname "$1")
	echo "$DIRNAME/$CLASSNAME.class"
}
function hasMain {
	local FILE=$(getClassFile "$1")
	cat -v $FILE | grep -E "\^A\^@\^Dmain\^A\^@\^V\(\[Ljava/lang/String;\)" &> /dev/null
}

# @vpl_script_description Using default javac, run JUnit if detected
# load common script and check programs
. common_script.sh

check_program javac
check_program java
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "javac -version" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
JUNIT4=/usr/share/java/junit4.jar
if [ -f $JUNIT4 ] ; then
	export CLASSPATH=$CLASSPATH:$JUNIT4
fi
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
	hasMain "$FILENAME"
	if [ "$?" -eq "0" ]	; then
		MAINCLASS=$(getClassName "$FILENAME")
		break
	fi
done
if [ "$MAINCLASS" = "" ] ; then
	for FILENAME in $SOURCE_FILES
	do
		hasMain "$FILENAME"
		if [ "$?" -eq "0" ]	; then
			MAINCLASS=$(getClassName "$FILENAME")
			break
		fi
	done
fi
if [ "$MAINCLASS" = "" ] ; then
# Search for junit4 test classes
	TESTCLASS=
	for FILENAME in $SOURCE_FILES
	do
		CLASSFILE=$(getClassFile "$FILENAME")
		grep "org/junit/" $CLASSFILE &> /dev/null
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
cat common_script.sh > vpl_execution
echo "export CLASSPATH=$CLASSPATH" >> vpl_execution
if [ ! "$MAINCLASS" = "" ] ; then
	echo "java -enableassertions $MAINCLASS \$@" >> vpl_execution
else
	echo "java org.junit.runner.JUnitCore $TESTCLASS \$@" >> vpl_execution
fi
chmod +x vpl_execution
for FILENAME in $SOURCE_FILES
do
	CLASSFILE=$(getClassFile "$FILENAME")
	grep -E "javax/swing/(JFrame|JDialog|JOptionPane|JApplet)" $CLASSFILE &> /dev/null
	if [ "$?" -eq "0" ]	; then
		mv vpl_execution vpl_wexecution
		break
	fi
done

