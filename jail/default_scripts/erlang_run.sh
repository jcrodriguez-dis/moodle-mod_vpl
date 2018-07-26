#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Erlang language
# Copyright (C) Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#@vpl_script_description Using escript and then erl
#load common script and check programs
. common_script.sh
check_program erl
check_program escript
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "erl +V" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_first_source_file erl
erlc $FIRST_SOURCE_FILE < /dev/null
cat common_script.sh > vpl_execution
if [ "$1" == "batch" ] ; then
	echo "escript $FIRST_SOURCE_FILE" >>vpl_execution
else
	echo "escript $FIRST_SOURCE_FILE" >>vpl_execution
	echo "erl" >>vpl_execution
fi

chmod +x vpl_execution

