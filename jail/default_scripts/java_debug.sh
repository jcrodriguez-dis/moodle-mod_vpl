#!/bin/bash
# $Id: java_debug.sh,v 1.7 2013-07-09 13:24:41 juanca Exp $
# Default Java language debug script for VPL
# Copyright (C) 2011 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

#load common script and check programs
. common_script.sh
check_program javac
check_program jdb
#compile
MAINCLASS=
javac -J-Xmx16m -g *.java
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
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
	echo "Class with \"public static void main(String[] arg)\" method not found"
	exit 0
fi
cat common_script.sh >vpl_execution
echo "java -Xdebug -Xmx16m -agentlib:jdwp=transport=dt_socket,server=y,suspend=y,address=127.0.0.1:$UID $MAINCLASS <&0 &"  >> vpl_execution
echo "jdb -J-Xmx16M -attach 127.0.0.1:$UID" >> vpl_execution

chmod +x vpl_execution
