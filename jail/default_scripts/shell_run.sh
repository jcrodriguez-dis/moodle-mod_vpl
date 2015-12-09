#!/bin/bash
# Default script to run Shell scripts
# Copyright Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
cat common_script.sh > vpl_execution
cat $VPL_SUBFILE0 >> vpl_execution
chmod +x vpl_execution
