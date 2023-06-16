#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Perl language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "perl -w" with first file
# load common script and check programs
. common_script.sh
check_program perl
if [ "$1" == "version" ] ; then
	get_program_version -v 5
fi
get_source_files perl prl pl pm
IFS=$'\n'
for file_name in $SOURCE_FILES
do
	perl -c "$file_name" 2>> .vpl_perl_errors
done
IFS=$SIFS
# Remove OKs compilations
cat .vpl_perl_errors | egrep -v "OK$"
get_first_source_file perl prl
cat common_script.sh > vpl_execution
echo "perl -w \"$FIRST_SOURCE_FILE\" \$@" >>vpl_execution
chmod +x vpl_execution
