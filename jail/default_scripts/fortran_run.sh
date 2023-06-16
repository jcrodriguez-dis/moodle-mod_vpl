#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Fortran language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using gfortran
# load VPL environment vars
. common_script.sh
check_program gfortran
if [ "$1" == "version" ] ; then
	get_program_version --version
fi 

get_source_files f for f90 f95 f03
# Generate file with source files
generate_file_of_files .vpl_source_files
# Compile
gfortran -o vpl_execution @.vpl_source_files
rm .vpl_source_files
