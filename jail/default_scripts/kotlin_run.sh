#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for compiling and running Kotlin language programs
# Copyright 2021 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using default kotlinc
# load common script and check programs
. common_script.sh
check_program java
check_program kotlinc
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "kotlinc -version &> .kotlinc_version" >> vpl_execution
	echo "cat .kotlinc_version | sed 's/.*kotlin/kotlin/'" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_source_files kt
# Generate file with source files
generate_file_of_files .vpl_source_files
echo "-include-runtime" > .vpl_kotlin_command_line
echo "-d vpl_execution.jar" >> .vpl_kotlin_command_line
cat .vpl_source_files >> .vpl_kotlin_command_line
# Compile
kotlinc @.vpl_kotlin_command_line
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
cat common_script.sh > vpl_execution
echo "java -jar vpl_execution.jar \$@" >> vpl_execution
chmod +x vpl_execution
