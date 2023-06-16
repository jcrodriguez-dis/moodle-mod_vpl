#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running ADA language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using GNAT with Ada 2005 features 
# load common script and check programs
. common_script.sh
check_program gnat

if [ "$1" == "version" ] ; then
    get_program_version -v
fi
get_first_source_file adb
# compile
gnat make -gnat2005 -gnatW8 -q -o vpl_execution "$FIRST_SOURCE_FILE"

