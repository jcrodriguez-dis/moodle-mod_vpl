#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Groovy language
# Copyright (C) 2021 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

function getClassName {
    #replace / for .
	local CLASSNAME=$(echo "$1" |sed 's/\//\./g')
	#remove file extension .javgroovya
	CLASSNAME=$(basename "$CLASSNAME" .groovy)
	echo $CLASSNAME
}
function getClassFile {
	#remove file extension .groovy
	local CLASSNAME=$(basename "$1" .groovy)
	local DIRNAME=$(dirname "$1")
	echo "$DIRNAME/$CLASSNAME.class"
}
function hasMain {
	local FILE=$(getClassFile "$1")
	if [ -f "$FILE" ] ; then
		cat -v "$FILE" | grep -E "\^A\^@\^Dmain\^A\^@\^V\(\[Ljava/lang/String;\)" &> /dev/null
	else
		return 1
	fi
}

# @vpl_script_description Using default javac, run JUnit if detected
# load common script and check programs
. common_script.sh

check_program groovyc
check_program groovy
# Generate filter to remove initial WARNING
REMOVE=$(groovy -version |& grep -c WARNING)
if [ "$REMOVE" != "0" ] ; then
	REMOVE=$(( $REMOVE + 1 ))
	FILTER="|& tail -n+$REMOVE"
fi

if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "groovy -version $FILTER" >> vpl_execution
	chmod +x vpl_execution
	exit
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

get_source_files groovy
# compile all .groovy files

groovyc $2 $SOURCE_FILES
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
if [ ! "$MAINCLASS" = "" ] ; then
	cat common_script.sh > vpl_execution
	echo "export CLASSPATH=$CLASSPATH" >> vpl_execution
	echo "groovy $MAINCLASS \$@ $FILTER" >> vpl_execution
	chmod +x vpl_execution
else
	echo "main method not found" >> vpl_execution
fi
