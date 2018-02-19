#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Ruby language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default ruby -rdebug
# load common script and check programs
. common_script.sh
check_program ruby
get_source_files ruby rb
cat common_script.sh > vpl_execution
echo "ruby -rdebug $SOURCE_FILE0" >>vpl_execution
chmod +x vpl_execution
