#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for accessing execution server command line
# Copyright (C) 2023 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Access execution server command line (save action updates files in server)
# load common script and check programs
. common_script.sh
check_program bash
if [ "$1" == "version" ] ; then
	get_program_version --version 3
fi
cp ./common_script.sh vpl_execution
cat vpl_environment.sh >> vpl_execution
cat >> vpl_execution << END_OF_SCRIPT
export TERM=ansi
rm vpl_environment.sh &>  /dev/null
rm common_script.sh &>  /dev/null
rm .vpl_* &> /dev/null
rm vpl_execution
/bin/bash
END_OF_SCRIPT
chmod +x vpl_execution
