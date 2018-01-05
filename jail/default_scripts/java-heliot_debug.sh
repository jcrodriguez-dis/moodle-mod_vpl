#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Java language
# Copyright (C) 2011 onwards Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using jGRASP
# load common script and check programs

. common_script.sh
#check_program jgrasp
get_source_files java
MAINFILE=$VPL_SUBFILE0

for FILENAME in $VPL_SUBFILES
do
     egrep "void[ \t]+main[ \t]*\(" $FILENAME &> /dev/null
     if [ "$?" -eq "0" ]    ; then
         MAINFILE=$FILENAME
         break
     fi
done
cat >vplexecution <<FIN
#!/bin/bash
cd $HOME
jgrasp $MAINFILE
xterm
FIN
#echo $HOME
#echo $VPL_SUBFILES
#ls .
#echo $SOURCE_FILES
#cat < vplexecution
mv vplexecution vpl_wexecution
