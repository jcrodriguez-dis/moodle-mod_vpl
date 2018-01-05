#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running VHDL language
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using gvhdl
# load common script and check programs
. common_script.sh
check_program gvhdl
if [ "$1" == "version" ] ; then
	exit
fi
get_source_files vhdl vhd
# compile
gvhdl $SOURCE_FILES

NAME=${VPL_SUBFILE0%.*}
if [ -f $NAME ] ; then
	mv $NAME vpl_execution
	chmod +x vpl_execution
else
	echo "============================================"
	echo "The first file name is the entity name"
	echo "Use lowercase in file names"
fi
