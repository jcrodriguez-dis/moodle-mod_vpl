#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Python language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using python2 pdb with the first file
# load common script and check programs
. common_script.sh
check_program python
get_first_source_file py
cat common_script.sh > vpl_execution
echo "python2 -m pdb $FIRST_SOURCE_FILE" >>vpl_execution
chmod +x vpl_execution
