#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Java language
# Copyright (C) 2011 onwards Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using jGRASP
# load common script and check programs

. common_script.sh
check_program jgrasp
if [ "$1" == "version" ] ; then
	get_program_version unknown
fi
get_source_files jar
for JARFILE in $SOURCE_FILES
do
	CLASSPATH=$CLASSPATH:$JARFILE
done
export CLASSPATH

get_first_source_file java
MAINFILE=$FIRST_SOURCE_FILE

for FILENAME in $VPL_SUBFILES
do
     egrep "void[ \t]+main[ \t]*\(" $FILENAME &> /dev/null
     if [ "$?" -eq "0" ]    ; then
         MAINFILE=$FILENAME
         break
     fi
done
cat common_script.sh > vplexecution
check_program x-terminal-emulator xterm

cat >>vplexecution <<FIN
jgrasp $MAINFILE
wait_end jgrasp
FIN
chmod +x vplexecution
mv vplexecution vpl_wexecution
