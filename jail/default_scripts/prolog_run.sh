#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Prolog language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program swipl
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "swipl -v" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
swipl -q -s $VPL_SUBFILE0 -t halt
cat common_script.sh > vpl_execution
if [ "$1" != "batch" ] ; then
	echo "swipl -q -L32M -s $VPL_SUBFILE0" >>vpl_execution
else
	predicate=$(basename $VPL_SUBFILE0 .pro)
	echo "$predicate ." > .swi_pred
	echo "halt." >> .swi_pred
	echo "swipl -q -L32M -s $VPL_SUBFILE0 < .swi_pred" >>vpl_execution
fi

chmod +x vpl_execution
