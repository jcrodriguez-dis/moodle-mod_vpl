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
export TERM=dump
# compile
if [ "$PROGRAM" == "ghdl" ] ; then
    ghdl -c $SOURCE_FILES 2>&1 | sed 's/\x1b\[[0-9;]*m//g'
    TOPENTITY="$(ghdl find-top 2>/dev/null)"
    if [ "$TOPENTITY" == "" ] ; then
        echo "No top entity found"
    else
    	echo "#!/bin/bash" > vpl_execution
		echo "ghdl -r $TOPENTITY" >> vpl_execution
    	chmod +x vpl_execution
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
