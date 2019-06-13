#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running HTML language
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "firefox" with the first file
# load common script and check programs
. common_script.sh
check_program x-www-browser firefox
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "$PROGRAM --version | head -n3" >> vpl_execution
	chmod +x vpl_execution
	exit
fi

compile_typescript
compile_scss

check_program x-www-browser firefox
get_first_source_file html htm
# Generate file with source files
cat > vpl_wexecution <<END_OF_SCRIPT
#!/bin/bash
$PROGRAM "$FIRST_SOURCE_FILE"
END_OF_SCRIPT
chmod +x vpl_wexecution
