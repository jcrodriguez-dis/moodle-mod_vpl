#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running VHDL language
# Copyright (C) 2023 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using ghdl or gvhdl
# load common script and check programs
. common_script.sh
check_program ghdl gvhdl
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
    if [ "$PROGRAM" == "ghdl" ] ; then
    	echo "ghdl -v | head -n3" >> vpl_execution
    else
    	echo "echo -n \"FreeHDL \"" >> vpl_execution
    	echo "freehdl-config --version" >> vpl_execution
    fi
	chmod +x vpl_execution
	exit
fi
get_source_files vhdl vhd
export TERM=dumb
# Process request based on compiler detected
if [ "$PROGRAM" == "ghdl" ] ; then
	get_first_source_file vhdl vhd
	for SOURCE_FILE in $SOURCE_FILES; do
	    ghdl -c $SOURCE_FILE &> /dev/null
	    TOPENTITY="$(ghdl --find-top 2>/dev/null)"
    	if [ "$TOPENTITY" != "" ] ; then
		SOURCE_FILE_TE=$SOURCE_FILE
			break
		fi
	done
   	if [ "$TOPENTITY" == "" ] ; then
		get_first_source_file vhdl vhd
		TOPENTITY=${FIRST_SOURCE_FILE%.*}
		SOURCE_FILE_TE=$FIRST_SOURCE_FILE
        echo "No top entity found using ghdl --find-top"
		echo "Using first filename as top entity name: $TOPENTITY"
	fi
	for SOURCE_FILE in $SOURCE_FILES; do
		ghdl -a $SOURCE_FILE
		if [ "$?" != "0" -a "$SOURCE_FILE" == "$SOURCE_FILE_TE" ] ; then
			TOPENTITY=""
		fi
	done
	if [ "$TOPENTITY" == "" ] ; then
		echo "================================================"
		echo "Compilation error: Check the code and try again."
		exit
	else
		ghdl -e $TOPENTITY
		if [ "$?" == "0" ] ; then
			{
				echo "#!/bin/bash"
				echo "ghdl -r $TOPENTITY"
			} > vpl_execution
			chmod +x vpl_execution
		else
			echo "================================================"
			echo "Top entity elaboration fails."
		fi
	fi
else
    gvhdl $SOURCE_FILES
    get_first_source_file vhdl vhd
    NAME=${FIRST_SOURCE_FILE%.*}
    if [ -f "$NAME" ] ; then
    	echo "#!/bin/bash" > vpl_execution
    	#interactive console
    	if [ "$1" != "batch" ] ; then
    		echo "./$NAME -q" >> vpl_execution
    	else
    		echo "./$NAME -q -cmd \"q\"" >> vpl_execution
    	fi
    	chmod +x vpl_execution
    else
    	echo "============================================"
    	echo "The first file name is the entity name"
    	echo "Use lowercase in file names"
    fi
fi
apply_run_mode
