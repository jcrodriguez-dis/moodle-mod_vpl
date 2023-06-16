#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for TypeScript language using NodeJs
# Copyright (C) 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using nodejs with the first file
# load common script and check programs
. common_script.sh
check_program tsc
if [ "$1" == "version" ] ; then
	get_program_version -v
fi
check_program nodejs
export TERM=dump
get_source_files ts
SAVEIFS=$IFS
IFS=$'\n'
for FILENAME in $SOURCE_FILES
do
	tsc "$FILENAME" | sed 's/\x1b\[[0-9;]*[a-zA-Z]//g'
done
IFS=$SAVEIFS

get_first_source_file ts
FIRST_SOURCE_FILE="${FIRST_SOURCE_FILE%.*}.js"
cat common_script.sh > vpl_execution
echo "nodejs \"$FIRST_SOURCE_FILE\" \$@" >> vpl_execution
chmod +x vpl_execution
