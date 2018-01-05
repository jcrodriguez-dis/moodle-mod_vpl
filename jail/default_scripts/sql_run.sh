#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running SQL language (sqlite3)
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using sqlite3
# load common script and check programs
. common_script.sh
check_program sqlite3
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "echo -n \"sqlite3 \"" >> vpl_execution
	echo "sqlite3 -version" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
#Generate execution script
cat common_script.sh > vpl_execution
#remove vpl.db
echo "if [ -f vpl.db ] ; then" >> vpl_execution
echo "rm vpl.db" >> vpl_execution
echo "fi" >> vpl_execution
#search and add .sql files not from submission
#save submission files
for FILENAME in $VPL_SUBFILES
do
	mv $FILENAME $FILENAME.vpl_save
	#security check
	if [ -f $FILENAME ] ; then
		rm $FILENAME
		echo "removed $FILENAME"
	fi
done
for FILENAME in *
do
	NAME=$(basename $FILENAME .sql)
	if [ "$FILENAME" != "$NAME" ] ; then
		echo "sqlite3 vpl.db < $FILENAME" >> vpl_execution
	fi
done
#restore submission files
for FILENAME in *.vpl_save
do
	NAME=$(basename $FILENAME .vpl_save)
	mv $FILENAME $NAME
done

#search and add .sql files from submission
for FILENAME in $VPL_SUBFILES
do
	NAME=$(basename $FILENAME .sql)
	if [ "$FILENAME" != "$NAME" ] ; then
		echo "sqlite3 vpl.db < $FILENAME" >> vpl_execution
	fi
done
#interactive console
if [ "$1" != "batch" ] ; then
	echo "sqlite3 vpl.db" >> vpl_execution
fi

chmod +x vpl_execution
