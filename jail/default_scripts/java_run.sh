#!/bin/bash
# $Id: java_run.sh,v 1.6 2012-09-24 15:13:22 juanca Exp $
# Default Java language run script for VPL
# Copyright (C) 2011 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

function getClassName {
    #replace / for .
	local CLASSNAME=$(echo "$1" |sed 's/\//\./g')
	#remove file extension .java
	CLASSNAME=$(basename "$CLASSNAME" .java)
	echo $CLASSNAME
}

#load common script and check programs
. common_script.sh

check_program javac
check_program java
JUNIT4=/usr/share/java/junit4.jar
if [ -f $JUNIT4 ] ; then
	export CLASSPATH=$CLASSPATH:$JUNIT4
fi
get_source_files java
#compile all .java files

javac -J-Xmx16m -Xlint:deprecation $SOURCE_FILES
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
#Search main procedure class
MAINCLASS=
for FILENAME in $VPL_SUBFILES
do
	egrep "void[ \t]+main[ \t]*\(" $FILENAME 2>&1 >/dev/null
	if [ "$?" -eq "0" ]	; then
		MAINCLASS=$(getClassName "$FILENAME")
		break
	fi
done
if [ "$MAINCLASS" = "" ] ; then
	for FILENAME in $SOURCE_FILES
	do
		egrep "void[ \t]+main[ \t]*\(" $FILENAME 2>&1 >/dev/null
		if [ "$?" -eq "0" ]	; then
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
		grep "org\.junit\." $FILENAME 2>&1 >/dev/null
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
	echo "java -Xmx16M -enableassertions $MAINCLASS" >> vpl_execution
else
	echo "java -Xmx16M org.junit.runner.JUnitCore $TESTCLASS" >> vpl_execution
fi
chmod +x vpl_execution
for FILENAME in $SOURCE_FILES
do
	grep -E "JFrame|JDialog" $FILENAME 2>&1 >/dev/null
	if [ "$?" -eq "0" ]	; then
		mv vpl_execution vpl_wexecution
		break
	fi
done

