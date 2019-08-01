#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Default Matlab/Octave language run script for VPL
# Copyright (C) 2016 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using Matlab or Octave with the first file
# load common script and check programs

. common_script.sh
check_program matlab octave
if [ "$1" == "version" ] ; then
	if [ "$PROGRAM" == "octave" ] ; then
		echo "#!/bin/bash" > vpl_execution
		echo "octave --no-window-system --version | head -n2" >> vpl_execution
		chmod +x vpl_execution
	else
		echo "#!/bin/bash" > vpl_execution
		echo "matlab --version | head -n2" >> vpl_execution
		chmod +x vpl_execution
	fi
	exit
fi
exec 2>&1
get_source_files m
get_first_source_file m
SIFS=$IFS
IFS=$'\n'
X11=
if [ ! -f vpl_evaluate.sh ] ; then
	for FILENAME in $SOURCE_FILES
	do
		grep -E "(^|[^A-Za-z0-9])(image|imagesc|figure|plot|contour|contourf|polar|pie|errorbar|quiver|compass|semilog|loglog|bar|hist|stairs|stem|scatter|pareto|mesh|surf|sombrero)( *)($|[(|;])" "$FILENAME" &> /dev/null
		if [ "$?" -eq "0" ] ; then
			X11=y
			break
		fi
	done
fi
IFS=$SIFS
MAIN=$FIRST_SOURCE_FILE
if [ "$PROGRAM" == "octave" ] ; then
	cat common_script.sh > vpl_execution
	chmod +x vpl_execution
	if [ "$X11" == "" ] ; then
		if [ "$1" == "batch" ] ; then
			echo "octave-cli \"$FIRST_SOURCE_FILE\"" >> vpl_execution
		else
			echo "octave --no-window-system -q --persist \"$FIRST_SOURCE_FILE\"" >> vpl_execution
		fi
		echo "wait_end octave" >> vpl_execution
	else
cat > .octaverc << "END_SCRIPT"
can_use_graphics_toolkit = exist("graphics_toolkit","file") | exist("graphics_toolkit","builtin");
if can_use_graphics_toolkit
	graphics_toolkit("gnuplot");
endif

END_SCRIPT
		check_program x-terminal-emulator xterm
		if [ "$1" == "batch" ] ; then
			echo "$PROGRAM -e octave -q --no-gui \"$FIRST_SOURCE_FILE\"" >> vpl_execution
		else
			echo "$PROGRAM -e octave -q --no-gui --persist \"$FIRST_SOURCE_FILE\"" >> vpl_execution
		fi
		echo "wait_end octave" >> vpl_execution
		mv vpl_execution vpl_wexecution
	fi
else
	PROGNAME=$(basename $MAIN .m)
	cp $MAIN startup.m
	cat common_script.sh > vpl_execution
	chmod +x vpl_execution
	if [ "$X11" == "" ] ; then
		echo "matlab -nosplash" >> vpl_execution
	else
		check_program x-terminal-emulator xterm
		echo "$PROGRAM -e matlab -nosplash" >> vpl_execution
		echo "wait_end mathlab" >> vpl_execution
		mv vpl_execution vpl_wexecution
	fi
fi
