#!/bin/bash
# $Id: sql_run.sh,v 1.4 2012-09-24 15:13:22 juanca Exp $
# Default SQL language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program sqlite3
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
echo "sqlite3 vpl.db" >> vpl_execution
chmod +x vpl_execution
