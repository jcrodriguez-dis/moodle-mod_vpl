#!/bin/bash
# $Id: c_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# Default C language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program Rscript
get_source_files r R
#Select first file
for FILENAME in $SOURCE_FILES
do
	SOURCE_FILE=$FILENAME
	break
done
#compile
cat common_script.sh > vpl_wexecution
echo "Rscript --default-packages=utils $SOURCE_FILE" >>vpl_wexecution
chmod +x vpl_wexecution