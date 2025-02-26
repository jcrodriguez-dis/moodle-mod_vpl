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
		. .localvplprofile 1>/dev/null 2>/dev/null
		rm .localvplprofile
	fi
fi
. vpl_environment.sh
#Use current lang
{
	for NEWLANG in $VPL_LANG en_US.UTF-8 C.utf8 POSIX C
	do
		export LC_ALL=$NEWLANG 2> .vpl_set_locale_error
		if [ -s .vpl_set_locale_error ] ; then
			rm .vpl_set_locale_error
			continue
		else
			break
		fi
	done
	rm .vpl_set_locale_error
} &>/dev/null

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
	local ERRFILE
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
		ERRFILE=.stderror
		{
			echo "$PROGRAM $1 1> $OUTPUTFILE 2>$ERRFILE < /dev/null"
			echo "[ \"\$?\" == \"0\" ] && cat $ERRFILE >> $OUTPUTFILE"
			echo "cat $OUTPUTFILE | head -n $nhl"
		} >> vpl_execution
	fi
	chmod +x vpl_execution
	exit
}

# Populate SOURCE_FILES, SOURCE_FILES_LINE and SOURCE_FILE0 with files
# of extensions passed. E.g. get_source_files cpp C
function get_source_files {
    local ext
    # Declare an array to hold all files
    declare -a files=()

    # 1. Collect all files with the given extensions
    for ext in "$@"; do
        if [ "$ext" == "NOERROR" ]; then
            break
        fi
        # Read find output into the array
        while IFS= read -r file; do
            # Remove leading "./" if present
            files+=("${file#./}")
        done < <(find . -name "*.$ext" -print)
    done

    # If no files were found (and not b64), exit with an error message.
    if [ ${#files[@]} -eq 0 ] && [ "$1" != "b64" ]; then
        if [ "$ext" == "NOERROR" ]; then
            return 1
        fi
        echo "To run this type of program you need some file with extension \"$@\""
        exit 0
    fi

    # 2. Reorder the list so that files matching VPL_SUBFILE0, VPL_SUBFILE1, … come first.
    declare -a ordered=()
    local i=0
    while true; do
        local varname="VPL_SUBFILE${i}"
        local vpl="${!varname}"
        if [ -z "$vpl" ]; then
            break
        fi

        # Search for vpl in the files array.
        local found_index=-1
        for j in "${!files[@]}"; do
            if [ "${files[j]}" == "$vpl" ]; then
                found_index="$j"
                break
            fi
        done

        if [ "$found_index" -ge 0 ]; then
            ordered+=("$vpl")
            # Remove the matched element from the files array.
            unset 'files[found_index]'
            # Re-index the array to avoid gaps.
            files=("${files[@]}")
        fi
        i=$((i+1))
    done

    # Append any remaining files to the ordered array.
    ordered+=("${files[@]}")

    # SOURCE_FILES as a newline-separated list.
    SOURCE_FILES=$(printf "%s\n" "${ordered[@]}")

    # Build SOURCE_FILES_LINE by escaping spaces.
    local escaped=()
    for file in "${ordered[@]}"; do
        # Replace spaces with "\ "
        escaped+=("$(echo "$file" | sed 's/ /\\ /g')")
    done
    SOURCE_FILES_LINE="${escaped[*]}"

    # Set SOURCE_FILE0 to the first file (if any)
    SOURCE_FILE0="${ordered[0]}"
    
    return 0
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
