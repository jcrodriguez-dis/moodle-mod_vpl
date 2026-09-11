#!/bin/bash
# Default common funtions for scripts of VPL
# Copyright (C) 2016 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# Run system profile in the current directory
if [ "$PROFILE_RAN" == "" ] ; then
	export PROFILE_RAN=yes
	if [ -f /etc/profile ] ; then
		cp /etc/profile .localvplprofile
		chmod +x .localvplprofile
		source .localvplprofile 1>/dev/null 2>/dev/null
		rm .localvplprofile
	fi
fi

# Load environment variables set by VPL
source vpl_environment.sh

#functions

function apply_run_mode {
	local RUN_MODE="$VPL_RUN_MODE"
	unset VPL_RUN_MODE
	case "$RUN_MODE" in
		2)
			# Text mode
			[ -f vpl_execution ] && return
			if [ -f vpl_wexecution ]; then
				mv vpl_wexecution vpl_execution
			fi
			;;
		3)
			# GUI mode
			[ -f vpl_wexecution ] && return
			if [ -f vpl_execution ]; then
				mv vpl_execution vpl_wexecution
			fi
			;;
		5)
			# Text in terminal emulator in GUI mode
			[ -f vpl_wexecution ] && return
			if [ -f vpl_execution ] ; then
				mv vpl_execution vpl_execution_in_gui
				echo "#!/bin/bash" > vpl_wexecution
				echo "source vpl_environment.sh" >> vpl_wexecution
				declare -f wait_end >> vpl_wexecution
				cat <<'END_SCRIPT' >> vpl_wexecution
# Run original vpl_execution script in terminal emulator in GUI mode
if command -v gnome-terminal &> /dev/null; then
	gnome-terminal -- bash -c "./vpl_execution_in_gui; echo; read -p 'Press Enter to continue...'"
elif command -v xterm &> /dev/null; then
	xterm -e bash -c "./vpl_execution_in_gui; echo; read -p 'Press Enter to continue...'"
elif command -v konsole &> /dev/null; then
	konsole --noclose -e "./vpl_execution_in_gui"
elif command -v xfce4-terminal &> /dev/null; then
	xfce4-terminal --hold --command="./vpl_execution_in_gui"
else
	# Run in non-terminal mode
	echo "No terminal emulator found."
	./vpl_execution_in_gui
fi
wait_end vpl_execution_in_gui

END_SCRIPT
				chmod +x vpl_wexecution
			fi
			;;
		*)
			# Default: do nothing
			;;
	esac
}

function apply_evaluation_mode {
	if [ "$VPL_EVALUATION_MODE" == "2" ]; then
		if [[ -f vpl_execution || -f vpl_wexecution ]] ; then
			[ -f vpl_execution ] && mv vpl_execution vpl_evaluation_in_gui
			[ -f vpl_wexecution ] && mv vpl_wexecution vpl_evaluation_in_gui
			cat default_evaluate_textingui.sh > vpl_execution
			chmod +x vpl_execution
		fi
	fi
}

# Wait until a program ($1 e.g. execution_int) of the current user ends. 
function wait_end {
	local PSRESFILE
	local PSOPTIONS=()
	PSRESFILE=.vpl_temp_search_program
	if ps -o user= -o args= >/dev/null 2>&1; then
		PSOPTIONS=(-o user= -o args=)
	fi
	# Wait until program start or until	5s
	for I in {1..5}
	do
		sleep 1s
		ps "${PSOPTIONS[@]}" > "$PSRESFILE"
		if [ -n "$USER" ]; then
			grep -E "(^|[[:space:]])${USER}([[:space:]]|$)" "$PSRESFILE" | grep -F -- "$1" &> /dev/null
		else
			grep -F -- "$1" "$PSRESFILE" &> /dev/null
		fi
		if [ "$?" == "0" ] ; then
			break
		fi
	done
	# Wait until program end
	while :
	do
		sleep 1s
		ps "${PSOPTIONS[@]}" > "$PSRESFILE"
		if [ -n "$USER" ]; then
			grep -E "(^|[[:space:]])${USER}([[:space:]]|$)" "$PSRESFILE" | grep -F -- "$1" &> /dev/null
		else
			grep -F -- "$1" "$PSRESFILE" &> /dev/null
		fi
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
			echo "head -n $nhl $OUTPUTFILE"
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
        echo "To run this type of program you need some file with extension \"$*\""
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
	touch "$1"
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
	local ext_checked=""
	local noerror=false
	local ext
	for ext in "$@"
	do
		if [ "$ext" == "NOERROR" ] ; then
			noerror=true
			break
		fi
		ext_checked="$ext_checked $ext"
	done
	local FILENAME
	local FILEVAR
    local i=0
    while true; do
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
		i=$((i+1))
	done
	if [ "$noerror" = true ] ; then
		return 1
	fi
	echo "To run this type of program you need some file with extension:$ext_checked"
	exit 0;
}

# Check program existence ($@) and set $PROGRAM and PROGRAMPATH
function check_program {
	PROGRAM=
	local programs_checked=""
	local check
	for check in "$@"
	do
		if [ "$check" == "NOERROR" ] ; then
    		continue
		fi
		local PROPATH
		PROPATH=$(command -v "$check")
		if [ "$PROPATH" == "" ] ; then
			programs_checked="$programs_checked $check"
			continue
		fi
		PROGRAM=$check
		PROGRAMPATH=$PROPATH
		return 0
	done
	if [ "$check" == "NOERROR" ] ; then
		return 1
	fi
	echo "The execution server needs to install one of the following programs"
	echo "to run this type of code:$programs_checked"
	exit 1;
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

function remove_vpl_executable_files {
	rm -f vpl_environment.sh
	rm -f common_script.sh
	rm -f .vpl_*
	rm -f vpl_execution
	rm -f vpl_wexecution
	rm -f vpl_execution_in_gui
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
			base64 -d < "$FILENAME" > "$BINARY"
		fi
	fi
done
SOURCE_FILES=""
if [ -x pre_vpl_run.sh ] ; then
	#Security Check: pre_vpl_run.sh was submitted by a student?
	VPL_NS=true
    i=0
    while true; do
		FILEVAR="VPL_SUBFILE${i}"
		FILENAME="${!FILEVAR}"
		if [ "" == "$FILENAME" ] ; then
			break
		fi
		if [ "$FILENAME" == "pre_vpl_run.sh" ] || [ "$FILENAME" == "pre_vpl_run.sh.b64" ] ; then
			VPL_NS=false
			break
		fi
		i=$((i+1))
	done
	if $VPL_NS ; then
		./pre_vpl_run.sh
		rm -f pre_vpl_run.sh
	fi
fi
IFS=$SAVEIFS
