#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running Clojure language
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

#@vpl_script_description Using default Clojure
#load common script and check programs

. common_script.sh
check_program clojure
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "clojure --eval \"(clojure-version)\"" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
get_first_source_file clj
cat common_script.sh > vpl_execution
echo "clojure $FIRST_SOURCE_FILE \$@" >>vpl_execution
chmod +x vpl_execution
