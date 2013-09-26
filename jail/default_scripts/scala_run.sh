#!/bin/bash
# Default Scala language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Authors: Lang Michael: michael.lang.ima10@fh-joanneum.at
#          Lückl Bernd: bernd.lueckl.ima10@fh-joanneum.at
#          Lang Johannes: johannes.lang.ima10@fh-joanneum.at
#load common script and check programs
. common_script.sh
check_program scala
cat common_script.sh > vpl_execution
echo "scala -nocompdaemon $VPL_SUBFILE0" >> vpl_execution
chmod +x vpl_execution
