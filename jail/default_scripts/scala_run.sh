#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Scala language
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Authors: Lang Michael: michael.lang.ima10@fh-joanneum.at
#          Lückl Bernd: bernd.lueckl.ima10@fh-joanneum.at
#          Lang Johannes: johannes.lang.ima10@fh-joanneum.at
#          Peter Salhofer 2015

# @vpl_script_description Using default scalac
# load common script and check programs
. common_script.sh
check_program scala
check_program scalac
if [ "$1" == "version" ] ; then
	get_program_version -version
fi
get_source_files scala
# Generate file with source files
generate_file_of_files .vpl_source_files
# Compile
scalac @.vpl_source_files
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
get_first_source_file scala
APP=${FIRST_SOURCE_FILE%.*}
cat common_script.sh > vpl_execution
echo "scala -nocompdaemon $APP \$@" >> vpl_execution

chmod +x vpl_execution
SIFS=$IFS
IFS=$'\n'
if [ ! -f vpl_evaluate.sh ] ; then
	for FILENAME in $SOURCE_FILES
	do
		grep -E "scala\.swing\.| swing\.|javax.swing" "$FILENAME" &> /dev/null
		if [ "$?" -eq "0" ]	; then
			mv vpl_execution vpl_wexecution
			break
		fi
	done
fi
IFS=$SIFS
