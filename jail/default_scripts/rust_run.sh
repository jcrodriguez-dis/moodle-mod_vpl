#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Rust code 
# Copyright (C) 2023 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using rustc with math and util libs
# load common script and check programs
. common_script.sh
export TERM=dumb
check_program rustc
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 
get_first_source_file rs
rustc "$FIRST_SOURCE_FILE" --crate-name vpl_execution
