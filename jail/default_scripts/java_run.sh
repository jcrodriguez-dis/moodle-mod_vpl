#!/bin/bash
# $Id: java_run.sh,v 1.6 2012-09-24 15:13:22 juanca Exp $
# Default Java language run script for VPL
# Copyright (C) 2011 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

#load common script and check programs
. common_script.sh
check_program javac
check_program java
JUNIT4=/usr/share/java/junit4.jar
if [ -f $JUNIT4 ] ; then
	export CLASSPATH=$CLASSPATH:$JUNIT4
fi
#compile all .java files
MAINCLASS=
javac -J-Xmx16m -Xlint:deprecation *.java
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
#Search main procedure class
for FILENAME in $VPL_SUBFILES
do
	grep "void[ \n\t]*main[ \n\t]*(" $FILENAME 2>&1 >/dev/null
	if [ "$?" -eq "0" -a "$MAINCLASS" = "" ]	; then
		MAINCLASS=$(basename $FILENAME .java)
		break
	fi
done
if [ "$MAINCLASS" = "" ] ; then
	for FILENAME in *.java
	do
		grep "void[ \n\t]*main[ \n\t]*(" $FILENAME 2>&1 >/dev/null
		if [ "$?" -eq "0" -a "$MAINCLASS" = "" ]	; then
			MAINCLASS=$(basename $FILENAME .java)
			break
		fi
	done
fi
if [ "$MAINCLASS" = "" ] ; then
#Search for junit4 test classes
	TESTCLASS=
	for FILENAME in *.java
	do
		grep "org\.junit\." $FILENAME 2>&1 >/dev/null
		if [ "$?" -eq "0" ]	; then
			TESTCLASS="$TESTCLASS $(basename $FILENAME .java)"
			break
		fi
	done
	if [ "$TESTCLASS" = "" ] ; then
		echo "Class with \"public static void main(String[] arg)\" method not found"
		exit 0
	fi
fi
cat common_script.sh > vpl_execution
if [ -f $JUNIT4 ] ; then
	echo "export CLASSPATH=$CLASSPATH" >> vpl_execution
fi
if [ ! "$MAINCLASS" = "" ] ; then
	echo "java -Xmx16M -enableassertions $MAINCLASS" >> vpl_execution
else
	echo "java -Xmx16M org.junit.runner.JUnitCore $TESTCLASS" >> vpl_execution
fi
chmod +x vpl_execution
