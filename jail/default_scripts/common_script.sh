#!/bin/bash
# $Id: common_script.sh,v 1.6 2013-04-18 17:14:35 juanca Exp $
# Default common utils for scripts of VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load VPL environment vars
. vpl_environment.sh
#Use current lang
export LC_ALL=$VPL_LANG 1>/dev/null 2>vpl_set_locale_error
#If current lang not available use en_US.utf8
if [ -s vpl_set_locale_error ] ; then
	export LC_ALL=en_US.utf8  1>/dev/null 2>/dev/null
fi
rm vpl_set_locale_error 1>/dev/null 2>/dev/null
stty erase ^H 1>/dev/null 2>/dev/null
#functions
function get_source_files {
	SOURCE_FILES=""
	for ext in "$@"
	do
		ls *.$ext >ext_exist 2>/dev/null
		if [ -s ext_exist ] ; then
			SOURCE_FILES="$SOURCE_FILES *.$ext"
		fi
	done
	rm ext_exist 1>/dev/null 2>/dev/null
}

function check_program {
	PROPATH=$(command -v $1)
	if [ "$PROPATH" == "" ] ; then
		echo "The jail need to install \"$1\" to run this type of program"
		exit 0;
	fi
}
#Decode BASE64 files
for FILENAME in *.b64
do
	if [ -f "$FILENAME" ] ; then
		BINARY=$(basename "$FILENAME" .b64)
		if [ ! -f  "$BINARY" ] ; then
			base64 -d "$FILENAME" > "$BINARY"
		fi
	fi
done
#Security Check: pre_vpl_run.sh was submitted by a student?
VPL_NS=true
for FILENAME in $VPL_SUBFILES
do
	if [ "$FILENAME" == "pre_vpl_run.sh" ] ; then
		VPL_NS=false
	fi
done
if $VPL_NS ; then
	if [ -x pre_vpl_run.sh ] ; then
		./pre_vpl_run.sh
	fi
fi
