#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Assambler for the Intel x86 architecture
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using NASM assambler 80x86
# load common script and check programs
. common_script.sh
check_program nasm
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "nasm -v" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
get_source_files asm
#compile
OBJFILES=
for FILENAME in $SOURCE_FILES
do
	NAME=$(basename "$FILENAME" .asm)
	nasm -f elf -o $NAME.o $FILENAME
	OBJFILES="$OBJFILES $NAME.o"
done
ld -o vpl_execution $OBJFILES

