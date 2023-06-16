#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running SQL language (sqlite3)
# Copyright (C) 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using sqlite3
# load common script and check programs
. common_script.sh
check_program sqlite3
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "echo -n \"SQLite3 \"" >> vpl_execution
	echo "sqlite3 -version" >> vpl_execution
	chmod +x vpl_execution
	exit
fi

DBFILE=vpl.db

#Generate execution script
cat common_script.sh > vpl_execution
#remove $DBFILE
if [ -f $DBFILE ] ; then
    rm $DBFILE
fi

function vpl_sql_save_files {
	local FILENAME
	local FILEVAR
	local i
	for i in {0..10000}
	do
		FILEVAR="VPL_SUBFILE${i}"
		FILENAME="${!FILEVAR}"
		if [ "" == "$FILENAME" ] ; then
			break
		fi
		mv "$FILENAME" "$FILENAME.vpl_save"
		#security check. Not moved => removed
		if [ -f "$FILENAME" ] ; then
			rm "$FILENAME"
			echo "removed $FILENAME"
		fi
	done
}

function vpl_sql_restore_files {
	local FILENAME
	local FILEVAR
	local i
	for i in {0..10000}
	do
		FILEVAR="VPL_SUBFILE${i}"
		FILENAME="${!FILEVAR}"
		if [ "" == "$FILENAME" ] ; then
			break
		fi
		mv "$FILENAME.vpl_save" "$FILENAME"
		#security check
		if [ ! -f "$FILENAME" ] ; then
			echo "\"$FILENAME\" lost"
		fi
	done
}

function vpl_sql_add_files {
	local FILENAME
	local FILEVAR
	local i
	for i in {0..10000}
	do
		FILEVAR="VPL_SUBFILE${i}"
		FILENAME="${!FILEVAR}"
		if [ "" == "$FILENAME" ] ; then
			break
		fi
		if [ "${FILENAME##*.}" == "sql" ] ; then
			echo "sqlite3 $DBFILE < \"$FILENAME\"" >> vpl_execution
		fi
	done
}

#search and add .sql files not from submission
#save submission files
vpl_sql_save_files

# add sh files to avoid zero files problem
get_source_files sql sh

SAVEIFS=$IFS
IFS=$'\n'
for FILENAME in $SOURCE_FILES
do
	if [ "${FILENAME##*.}" == "sql" ] ; then
		sqlite3 $DBFILE < "$FILENAME"
	fi
done
IFS=$SAVEIFS

#restore submission files
vpl_sql_restore_files

#search and add .sql files from submission
vpl_sql_add_files

#interactive console
if [ "$1" != "batch" ] ; then
	echo "sqlite3 $DBFILE" >> vpl_execution
fi

chmod +x vpl_execution
