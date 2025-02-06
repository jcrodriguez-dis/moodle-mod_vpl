#!/bin/bash
# Default common funtions for scripts of VPL
# Copyright (C) 2016 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load VPL environment vars
if [ "$PROFILE_RUNNED" == "" ] ; then
	export PROFILE_RUNNED=yes
	if [ -f /etc/profile ] ; then
		cp /etc/profile .localvplprofile
		chmod +x .localvplprofile
		. .localvplprofile
		rm .localvplprofile
	fi
fi
. vpl_environment.sh
#Use current lang
export LC_ALL=$VPL_LANG 1>/dev/null 2>vpl_set_locale_error
#If current lang not available use en_US.UTF-8
if [ -s vpl_set_locale_error ] ; then
	export LC_ALL=en_US.UTF-8  1>/dev/null 2>/dev/null
fi
rm vpl_set_locale_error 1>/dev/null 2>/dev/null
#functions

# Wait until a program ($1 e.g. execution_int) of the current user ends. 
function wait_end {
	local PSRESFILE
	PSRESFILE=.vpl_temp_search_program
	#wait start until 5s
	for I in 1 .. 5
	do
		sleep 1s
		ps -f -u $USER > $PSRESFILE
		grep $1 $PSRESFILE &> /dev/null
		if [ "$?" == "0" ] ; then
			break
		fi
	done
	while :
	do
		sleep 1s
		ps -f -u $USER > $PSRESFILE
		grep $1 $PSRESFILE &> /dev/null
		if [ "$?" != "0" ] ; then
			rm $PSRESFILE
			return
		fi
	done
}

# Adds code to vpl_execution for getting the version of $PROGRAM
# $1: version command line switch (e.g. -version)
# $2: number of lines to show. Default 2
function get_program_version {
	local OUTPUTFILE
	local nhl
	echo $PROGRAM $1 $2
	if [ "$2" == "" ] ; then
		nhl=2
	else
		nhl=$2
	fi
	
	echo "#!/bin/bash" > vpl_execution
	if [ "$1" == "unknown" ] ; then
		echo "echo \"$PROGRAM version unknown\"" >> vpl_execution
	else
		OUTPUTFILE=.stdoutput
		echo "$PROGRAM $1 1> $OUTPUTFILE 2>/dev/null < /dev/null" >> vpl_execution
		echo "cat $OUTPUTFILE | head -n $nhl" >> vpl_execution
	fi
	chmod +x vpl_execution
	exit
}

# Populate SOURCE_FILES, SOURCE_FILES_LINE and SOURCE_FILE0 with files
# of extensions passed. E.g. get_source_files cpp C
function get_source_files {
	local ext
	SOURCE_FILES=""
	SOURCE_FILES_LINE=""
	for ext in "$@"
	do
		if [ "$ext" == "NOERROR" ] ; then
			break
		fi
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

    if [ "$SOURCE_FILES" != "" -o "$1" == "b64" ] ; then
		local file_name
		local SIFS=$IFS
		IFS=$'\n'
		for file_name in $SOURCE_FILES
		do
			SOURCE_FILE0=$file_name
			break
		done
		IFS=$SIFS
		return 0
	fi
	if [ "$ext" == "NOERROR" ] ; then
		return 1
	fi

	echo "To run this type of program you need some file with extension \"$@\""
	exit 0;
}

# Take SOURCE_FILES and write at $1 file
function generate_file_of_files {
	if [ -f "$1" ] ; then
		rm "$1"
	fi
	touch $1 
	local file_name
	local SIFS=$IFS
	IFS=$'\n'
	for file_name in $SOURCE_FILES
	do
		if [ "$2" == "" ] ; then
			echo "\"$file_name\"" >> "$1"
		else
			echo "$file_name" >> "$1"
		fi
	done
	IFS=$SIFS
}

# Set FIRST_SOURCE_FILE to the first VPL_SUBFILE# with extension in parameters $@
function get_first_source_file {
	local ext
	local FILENAME
	local FILEVAR
	local i
	for i in {0..100000}
	do
		FILEVAR="VPL_SUBFILE${i}"
		FILENAME="${!FILEVAR}"
		if [ "" == "$FILENAME" ] ; then
			break
		fi
		for ext in "$@"
		do
		    if [ "${FILENAME##*.}" == "$ext" ] ; then
		        FIRST_SOURCE_FILE=$FILENAME
		        return 0
	    	fi
		done
	done
	if [ "$ext" == "NOERROR" ] ; then
		return 1
	fi
	echo "To run this type of program you need some file with extension \"$@\""
	exit 0;
}

# Check program existence ($@) and set $PROGRAM and PROGRAMPATH
function check_program {
	PROGRAM=
	local check
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
	if [ "$check" == "NOERROR" ] ; then
		return 1
	fi
	echo "The execution server needs to install \"$1\" to run this type of program"
	exit 0;
}

# Compile 
function compile_typescript {
	check_program tsc NOERROR
	if [ "$PROGRAM" == "" ] ; then
		return 0
	fi
	get_source_files ts NOERROR
	SAVEIFS=$IFS
	IFS=$'\n'
	for FILENAME in $SOURCE_FILES
	do
		tsc "$FILENAME" | sed 's/\x1b\[[0-9;]*[a-zA-Z]//g'
	done
	IFS=$SAVEIFS
}

function compile_scss {
	check_program sass NOERROR
	if [ "$PROGRAM" == "" ] ; then
		return 0
	fi
	get_source_files scss NOERROR
	SAVEIFS=$IFS
	IFS=$'\n'
	for FILENAME in $SOURCE_FILES
	do
		sass "$FILENAME"
	done
	IFS=$SAVEIFS
}


#Decode BASE64 files
get_source_files b64
SAVEIFS=$IFS
IFS=$'\n'
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
	if [ "$FILENAME" == "pre_vpl_run.sh" ] || [ "$FILENAME" == "pre_vpl_run.sh.b64" ] ; then
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
