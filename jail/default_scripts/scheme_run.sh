#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Scheme language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using mzscheme
# load common script and check programs
. common_script.sh
check_program mzscheme
if [ "$1" == "version" ] ; then
	get_program_version -v
fi
get_first_source_file scm s
cat common_script.sh > vpl_execution
echo "mzscheme -f \"$FIRST_SOURCE_FILE\"" >>vpl_execution
chmod +x vpl_execution
