#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running R language
# Copyright (C) 2017 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default R
# load common script and check programs
. common_script.sh
check_program R
if [ "$1" == "version" ] ; then
	get_program_version --version 3
fi

#Select first file
get_first_source_file r R

cat common_script.sh > vpl_execution
echo "Rscript --vanilla \"$FIRST_SOURCE_FILE\"" >>vpl_execution
chmod +x vpl_execution

apply_run_mode

# Running R Code
# By default, code is run in text mode using Rscript.
# Remember using readLines(file("stdin"), n = 1) to read input lines.
# To run in a GUI environment, either:
#   - Add a comment with @vpl_run_textingui_mode, or
#   - Change the run mode in the Moodle activity settings.
# To display graphics, use the following steps:
#   - Save the plot using png("filename.png") or jpeg("filename.jpg").
#   - Close the graphics device with dev.off().
#   - Open the image using:
#       system("nohup xdg-open filename.png &")
#       or
#       system("nohup xdg-open filename.png")

