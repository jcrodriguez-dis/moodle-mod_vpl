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
	get_program_version -v
fi

PBITS=32
uname -a | grep "x86_64" &> /dev/null
if [ "$?" == "0" ] ; then
	PBITS=64
fi
get_source_files asm
#compile
SIFS=$IFS
IFS=$'\n'
rm .vpl_object_files 2> /dev/null
touch .vpl_object_files
for FILENAME in $SOURCE_FILES
do
	BITS=$PBITS
	grep -i "^\s*bits 64" "$FILENAME" &> /dev/null
	if [ "$?" == "0" ] ; then
		BITS=64
	fi
	grep -i "^\s*bits 32" "$FILENAME" &> /dev/null
	if [ "$?" == "0" ] ; then
		BITS=32
	fi
	nasm -f "elf$BITS" "$FILENAME"
	echo "\"${FILENAME/%.asm}.o\"" >> .vpl_object_files
done
IFS=$'\n'
ld -o vpl_execution @.vpl_object_files
rm .vpl_object_files
IFS=$SIFS
