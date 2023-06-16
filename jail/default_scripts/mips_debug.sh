#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Assambler for the Intel x86 architecture
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description MIPS R2000/R3000 using xspim emulator 
# load common script and check programs
. common_script.sh
if [ "$1" == "version" ] ; then
	exit
fi
check_program xspim
get_source_files s
cat common_script.sh > vpl_wexecution
echo "xspim -file \"$SOURCE_FILE0\"" >> vpl_wexecution
chmod +x vpl_wexecution
