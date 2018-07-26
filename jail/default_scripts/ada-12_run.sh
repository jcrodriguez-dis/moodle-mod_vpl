#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running ADA language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using GNAT with Ada 2012 features 
# load common script and check programs
. common_script.sh
check_program gnat

# compile
if [ "$1" == "version" ] ; then
    echo "#!/bin/bash" > vpl_execution
    echo "gnat 1>.aux 2>/dev/null" >> vpl_execution
    echo "cat .aux | head -n2" >> vpl_execution
    chmod +x vpl_execution
else
    get_first_source_file adb
    gnat make -gnatW8 -q -o vpl_execution "$FIRST_SOURCE_FILE"
fi
