#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Scala language
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Authors: Lang Michael: michael.lang.ima10@fh-joanneum.at
#          Lückl Bernd: bernd.lueckl.ima10@fh-joanneum.at
#          Lang Johannes: johannes.lang.ima10@fh-joanneum.at
#          Peter Salhofer 2015

# @vpl_script_description Using default scalac
# load common script and check programs
. common_script.sh
check_program scala
check_program scalac
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "scalac -version" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_first_source_file scala
APP=${FIRST_SOURCE_FILE%.*}
scalac $FIRST_SOURCE_FILE
if [ "$?" -ne "0" ] ; then
	echo "Not compiled"
 	exit 0
fi
cat common_script.sh > vpl_execution
echo "scala -nocompdaemon $APP \$@" >> vpl_execution
chmod +x vpl_execution
grep -E "scala\.swing\.| swing\.|javax.swing" $FIRST_SOURCE_FILE &> /dev/null
if [ "$?" -eq "0" ]	; then
	mv vpl_execution vpl_wexecution
fi

