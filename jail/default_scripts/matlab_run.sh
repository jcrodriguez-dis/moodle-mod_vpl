#!/bin/bash
# $Id: matlab_run.sh,v 1.10 2013-07-09 13:28:31 juanca Exp $
# Default Matlab/Octave language run script for VPL
# Copyright (C) 2011 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

#load common script and check programs
. common_script.sh
exec 2>&1
get_source_files m
X11=
for FILENAME in $SOURCE_FILES
do
	grep -E "image|imagesc|plot|contour|polar|pie|errorbar|quiver|compass|semilog|loglog|bar|hist|stairs|stem|scatter|pareto|mesh|surf|sombrero" $FILENAME 2>&1 >/dev/null
	if [ "$?" -eq "0" ] ; then
		X11=y
		break
	fi
done
MAIN=
for FILENAME in $SOURCE_FILES
do
	MAIN=$FILENAME
	break
done
if [ "$(command -v matlab)" == "" ] ; then
	if [ "$(command -v octave)" == "" ] ; then
		echo "The jail-server need to install "Octave" or "Matlab" to run this type of program"
		exit 0;
	else
		cat common_script.sh > vpl_execution
		cp $MAIN .octaverc
		chmod +x vpl_execution
		if [ "$X11" == "" ] ; then
			echo "octave --no-window-system -q" >> vpl_execution
		else
			check_program xterm
			echo "xterm -e octave -q --persist" >> vpl_execution
			mv vpl_execution vpl_wexecution
		fi
	fi
else
	PROGNAME=$(basename $MAIN .m)
	cp $MAIN startup.m
	cat common_script.sh > vpl_execution
	chmod +x vpl_execution
	if [ "$X11" == "" ] ; then
		echo "matlab -nosplash" >> vpl_execution
	else
		check_program xterm
		echo "xterm -e matlab -nosplash" >> vpl_wexecution
		mv vpl_execution vpl_wexecution
	fi
fi

