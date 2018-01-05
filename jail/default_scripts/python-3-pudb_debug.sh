#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Python language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using python3 PUDB with the first file
# load common script and check programs
. common_script.sh
check_program python
cat common_script.sh > vpl_wexecution
echo "xterm -e python3 -m pudb $VPL_SUBFILE0" >>vpl_wexecution
chmod +x vpl_wexecution
