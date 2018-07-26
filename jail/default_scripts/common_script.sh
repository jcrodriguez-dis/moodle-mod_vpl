#!/bin/bash
# Default common funtions for scripts of VPL
# Copyright (C) 2016 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load VPL environment vars
. vpl_environment.sh
#Use current lang
export LC_ALL=$VPL_LANG 1>/dev/null 2>vpl_set_locale_error
#If current lang not available use en_US.UTF-8
if [ -s vpl_set_locale_error ] ; then
	export LC_ALL=en_US.UTF-8  1>/dev/null 2>/dev/null
fi
rm vpl_set_locale_error 1>/dev/null 2>/dev/null
#functions
function get_source_files {
	local ext
	SOURCE_FILES=""
	SOURCE_FILES_LINE=""
	for ext in "$@"
	do
	    local source_files_ext="$(find . -name "*.$ext" -print | sed 's/^.\///g' | sed 's/ /\\ /g')"
	    if [ "$SOURCE_FILES_LINE" == "" ] ; then
	        SOURCE_FILES_LINE="$source_files_ext"
	    else
	        SOURCE_FILES_LINE=$(echo -en "$SOURCE_FILES_LINE\n$source_files_ext")
	    fi
	    local source_files_ext_s="$(find . -name "*.$ext" -print | sed 's/^.\///g')"
	    if [ "$SOURCE_FILES" == "" ] ; then
	        SOURCE_FILES="$source_files_ext_s"
	    else
	        SOURCE_FILES=$(echo -en "$SOURCE_FILES\n$source_files_ext_s")
	    fi
	done
	local file_name
    if [ "$SOURCE_FILES_LINE" != "" -o "$1" == "b64" ] ; then
		for file_name in "$SOURCE_FILES"
		do
			SOURCE_FILE0="$file_name"
			return 0
		done
	fi
	echo "To run this type of program you need some file with extension \"$@\""
	exit 0;
}

function get_first_source_file {
	local ext
	local FILE
	for FILE in $VPL_SUBFILES
	do
		for ext in "$@"
		do
		    if [ "${FILE##*.}" == "$ext" ] ; then
		        FIRST_SOURCE_FILE="$FILE"
		        return 0
	    	fi
		done
	done
	echo "To run this type of program you need some file with extension \"$@\""
	exit 0;
}

function check_program {
	PROGRAM=
	for check in "$@"
	do
		local PROPATH=$(command -v $check)
		if [ "$PROPATH" == "" ] ; then
			continue
		fi
		PROGRAM=$check
		PROGRAMPATH=$PROPATH
		return 0
	done
	echo "The execution server needs to install \"$1\" to run this type of program"
	exit 0;
}

#Decode BASE64 files
get_source_files b64
SAVEIFS=$IFS
IFS=$(echo -en "\n\b")
for FILENAME in $SOURCE_FILES
do
	if [ -f "$FILENAME" ] ; then
		BINARY=$(echo "$FILENAME" | sed -r "s/\.b64$//")
		if [ ! -f  "$BINARY" ] ; then
			base64 -i -d "$FILENAME" > "$BINARY"
		fi
	fi
done
SOURCE_FILES=""
#Security Check: pre_vpl_run.sh was submitted by a student?
VPL_NS=true
for FILENAME in $VPL_SUBFILES
do
	if [ "$FILENAME" == "pre_vpl_run.sh" ] ; then
		VPL_NS=false
		break
	fi
done
IFS=$SAVEIFS
if $VPL_NS ; then
	if [ -x pre_vpl_run.sh ] ; then
		./pre_vpl_run.sh
	fi
fi
