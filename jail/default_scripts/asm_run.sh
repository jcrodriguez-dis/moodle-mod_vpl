#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# Default ADA language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program nasm
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

