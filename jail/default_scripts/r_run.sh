#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running R language
# Copyright (C) 2017 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default R
# load common script and check programs
. common_script.sh
check_program Rscript
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "R --version | head -n3" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_source_files r R
#Select first file
for FILENAME in $SOURCE_FILES
do
	SOURCE_FILE=$FILENAME
	break
done
#compile
cat common_script.sh > vpl_wexecution
cat  $SOURCE_FILE >> .Rprofile
if [ "$1" == "batch" ] ; then
	echo "xterm -e R --vanilla -f $SOURCE_FILE" >>vpl_wexecution
else
	echo "xterm -e R -q" >>vpl_wexecution
fi
chmod +x vpl_wexecution
