#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Verilog language
# Copyright (C) 2015 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using iverilog
# load common script and check programs
. common_script.sh
check_program iverilog
if [ "$1" == "version" ] ; then
	get_program_version -V 3
fi
get_source_files v
generate_file_of_files .vpl_source_files NOQUOTE
#compile
iverilog -ovpl_execution -f.vpl_source_files
