#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Minizinc constraint modeling language code
# Copyright (C) 2021 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "go run" with first file
# load common script and check programs

. common_script.sh
check_program minizinc mzn-fzn
if [ "$1" == "version" ] ; then
	get_program_version --version
fi
get_first_source_file mzn
if [ "$PROGRAM" == "minizinc" ] ; then
	SOLVER="--solver Gecode"
fi
DATAFILEBASE=$(basename "$FIRST_SOURCE_FILE" .mzn)
if [ -f "$DATAFILEBASE.dzn" ] ; then
	DATAFILE="$DATAFILEBASE.dzn"
else
	DATAFILE=
fi
echo "#!/bin/bash" > vpl_execution
if [ "$DATAFILE" == "" ] ; then
	echo "$PROGRAM $SOLVER \"$FIRST_SOURCE_FILE\"" >> vpl_execution
else
	echo "$PROGRAM $SOLVER \"$FIRST_SOURCE_FILE\" \"$DATAFILE\"" >> vpl_execution
fi
chmod +x vpl_execution
