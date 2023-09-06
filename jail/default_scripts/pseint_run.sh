#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running PSeInt language https://pseint.sourceforge.net
# Copyright (C) 2014 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using pseint with the first file
# load common script and check programs
. common_script.sh
check_program pseint
if [ "$1" == "version" ] ; then
        get_program_version --version
fi
get_first_source_file psc
cat common_script.sh > vpl_execution
echo "/pseint/bin/pseint --nouser \"$FIRST_SOURCE_FILE\" \$@" >>vpl_execution
chmod +x vpl_execution
