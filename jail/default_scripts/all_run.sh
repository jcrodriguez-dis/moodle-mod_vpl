#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Hello program (in console) of available languages
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Run a "hello world" program for each programming language available

COMMON_SCRIPT_SAVED=.common_script.sav
cp common_script.sh $COMMON_SCRIPT_SAVED
cat common_script.sh > all_execute
. common_script.sh
#Remove student files
for FILENAME in $VPL_SUBFILES
do
	rm "$FILENAME" &>/dev/null
done

NG=0
FILES=*_hello.sh
touch .tuierrors
for HELLOSCRIPT in $FILES
do
	typeset -u LANGUAGE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$//")
	RUNSCRIPT=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_run.sh/")
	VPLEXE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_execute.sh/")
	echo -n "$LANGUAGE:"
	rm .curerror &>/dev/null
	. $HELLOSCRIPT &>.curerror
	cp $COMMON_SCRIPT_SAVED common_script.sh
	echo "export VPL_SUBFILE0=\"$VPL_SUBFILE0\"" >> common_script.sh
	echo "export VPL_SUBFILE1=\"$VPL_SUBFILE1\"" >> common_script.sh
	echo "export SOURCE_FILE0=\"$VPL_SUBFILE0\"" >> common_script.sh
	echo "export SOURCE_FILE1=\"$VPL_SUBFILE1\"" >> common_script.sh
	eval ./$RUNSCRIPT batch &>>.curerror
	if [ -f vpl_execution ] ; then
		let "NG=NG+1"
		mv vpl_execution $VPLEXE
		echo " Compiled"
		echo "printf \"%2d %s: \" $NG $LANGUAGE" >> all_execute
		echo "[ -f .hello_fail ] && rm .hello_fail"  >> all_execute
		if [ "$INPUT_TEXT" == "" ] ; then
			echo "./$VPLEXE 2>.hello_fail" >> all_execute
		else
			echo "echo \"$INPUT_TEXT\" | ./$VPLEXE 2>.hello_fail" >> all_execute
			unset INPUT_TEXT
		fi
		echo "[ -s .hello_fail ] && echo \"Failed\"" >> all_execute
		echo "[ -f \"$VPL_SUBFILE0\" ] && rm \"$VPL_SUBFILE0\"" >> all_execute
	else
		if [ -f vpl_wexecution ] ; then
			echo " Use debug button to run graphic Hello World!"
			rm vpl_wexecution
		else
			echo " Hello program not generated"
		fi
	fi
	if [ -s .curerror ] ; then
		echo >> .tuierrors
		echo "$LANGUAGE: The compilation/preparation of $LANGUAGE has generated the folloging menssages:" >> .tuierrors
		cat .curerror >> .tuierrors
	fi
done

mv all_execute vpl_execution
chmod +x vpl_execution
echo
cat .tuierrors
