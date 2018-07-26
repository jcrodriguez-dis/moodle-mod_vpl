#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Prolog language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using swipl with the first file
# load common script and check programs
. common_script.sh
check_program swipl
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "swipl -v" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_first_source_file pro pl
swipl -q -s $FIRST_SOURCE_FILE -t halt 1 > /dev/null < /dev/null
cat common_script.sh > vpl_execution
if [ "$1" == "batch" ] ; then
	echo "swipl -q -L32M -s $FIRST_SOURCE_FILE -t vpl_hello" >>vpl_execution
else
	echo "swipl -q -L32M -s $FIRST_SOURCE_FILE" >>vpl_execution
fi

chmod +x vpl_execution

