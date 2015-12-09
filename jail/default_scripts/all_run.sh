#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Hello from all languages
# Copyright (C) 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat common_script.sh > all_execute
FILES=*_hello.sh
for HELLOSCRIPT in $FILES
do
	LANGUAGE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$//")
	RUNSCRIPT=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_run.sh/")
	VPLEXE=$(echo "$HELLOSCRIPT" | sed -r "s/_hello.sh$/_execute.sh/")
	echo "Generating Hello for $LANGUAGE"
	. $HELLOSCRIPT
	. $RUNSCRIPT
	if [ -f vpl_execute ] ; then
		mv vpl_execute $VPLEXE
		echo "echo \"Running hello from $LANGUAGE\"" >> all_execute
		echo "./$VPLEXE" >> all_execute
	else
		echo "Error: Hello for $LANGUAGE not generated"
	fi
done
mv all_execute vpl_execute
chmod +x vpl_execute
