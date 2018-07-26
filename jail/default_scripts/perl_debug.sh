#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Perl language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "perl -d" with first file
# load common script and check programs
. common_script.sh
check_program perl
get_first_source_file perl prl
cat common_script.sh > vpl_execution
echo "perl -d $FIRST_SOURCE_FILE" >>vpl_execution
chmod +x vpl_execution
