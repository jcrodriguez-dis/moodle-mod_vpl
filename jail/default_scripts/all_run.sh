#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Hello program (in console) of available languages
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Run a "hello world" program for each programming language available

ROOT="$(pwd)"
ALL_EXECUTE="$ROOT/all_execute"
. common_script.sh
#Remove student files
for FILENAME in $VPL_SUBFILES ; do
	rm "$FILENAME" &>/dev/null
done

cat common_script.sh > "$ALL_EXECUTE"
NG=0
FILES=*_hello.sh
touch .tuierrors
for HELLOSCRIPT in $FILES
do
	typeset -u LANGUAGE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$//")
	RUNSCRIPT=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_run.sh/")
	echo -n "$LANGUAGE:"
	mkdir $LANGUAGE
	cp common_script.sh $LANGUAGE
	cp vpl_environment.sh $LANGUAGE
	mv $HELLOSCRIPT $LANGUAGE
	mv $RUNSCRIPT $LANGUAGE
	cd $LANGUAGE
	. $HELLOSCRIPT &>.curerror
	echo "export VPL_SUBFILE0=\"$VPL_SUBFILE0\"" >> common_script.sh
	echo "export VPL_SUBFILE1=\"$VPL_SUBFILE1\"" >> common_script.sh
	echo "export SOURCE_FILE0=\"$VPL_SUBFILE0\"" >> common_script.sh
	echo "export SOURCE_FILE1=\"$VPL_SUBFILE1\"" >> common_script.sh
	eval ./$RUNSCRIPT batch &>>.curerror
	if [ -f vpl_execution ] ; then
		let "NG=NG+1"
		echo " Compiled"
		{
			echo "printf \"%2d %s: \" $NG $LANGUAGE"
			echo "[ -f .hello_fail ] && rm .hello_fail"
			echo "cd $LANGUAGE"
			if [ "$INPUT_TEXT" == "" ] ; then
				echo "./vpl_execution 2>.hello_fail"
			else
				echo "echo \"$INPUT_TEXT\" | ./vpl_execution 2>.hello_fail"
				unset INPUT_TEXT
			fi
			echo "[ -s .hello_fail ] && cat .hello_fail"
			echo "[ -s .hello_fail ] && echo \"Failed\""
			echo "[ -f \"$VPL_SUBFILE0\" ] && rm \"$VPL_SUBFILE0\""
			echo "cd $ROOT"
		} >> "$ALL_EXECUTE"
	else
		if [ -f vpl_wexecution ] ; then
			echo " Use debug button to run graphic Hello World!"
			rm vpl_wexecution
		else
			echo " Hello program not generated"
		fi
	fi
	if [ -s .curerror ] ; then
		{
			echo
			echo "$LANGUAGE: The compilation/preparation of $LANGUAGE has generated the folloging menssages:"
			cat .curerror
		} >> "$ROOT/.tuierrors"
	fi
	cd "$ROOT"
done

mv "$ALL_EXECUTE" vpl_execution
chmod +x vpl_execution
echo
cat .tuierrors
