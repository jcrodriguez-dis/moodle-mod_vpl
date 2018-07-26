#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Shell scripts
# Copyright Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default /bin/bash
# load common script and check programs
. common_script.sh
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "bash --version | head -n3" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_first_source_file sh
cat common_script.sh > vpl_execution
cat $FIRST_SOURCE_FILE >> vpl_execution
chmod +x vpl_execution
