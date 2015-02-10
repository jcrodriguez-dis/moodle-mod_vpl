#!/bin/bash
# Default Matlab/Octave language run script for VPL
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

#load common script and check programs
. common_script.sh
exec 2>&1
get_source_files m
X11=
if [ ! -f vpl_evaluate.sh ] ; then
	for FILENAME in $SOURCE_FILES
	do
		grep -E "(^|[^A-Za-z0-9])(image|imagesc|figure|plot|contour|contourf|polar|pie|errorbar|quiver|compass|semilog|loglog|bar|hist|stairs|stem|scatter|pareto|mesh|surf|sombrero)( *)($|[(|;])" $FILENAME 2>&1 >/dev/null
		if [ "$?" -eq "0" ] ; then
			X11=y
			break
		fi
	done
fi
MAIN=$VPL_SUBFILE0

if [ "$(command -v matlab)" == "" ] ; then
	if [ "$(command -v octave)" == "" ] ; then
		echo "The jail-server need to install "Octave" or "Matlab" to run this type of program"
		exit 0;
	else
		cat common_script.sh > vpl_execution
		cat > .octaverc << "END_SCRIPT"
can_use_graphics_toolkit =exist("graphics_toolkit","file") | exist("graphics_toolkit","builtin");
if can_use_graphics_toolkit
	graphics_toolkit("gnuplot");
endif

END_SCRIPT
		cat $MAIN >> .octaverc
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
		echo "xterm -e matlab -nosplash" >> vpl_execution
		mv vpl_execution vpl_wexecution
	fi
fi
