#!/bin/bash
# $Id: haskell_run.sh,v 1.4 2012-09-24 15:13:22 juanca Exp $
# Default Haskell language run script for VPL
# Copyright (C) 2014 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodriguez-del-Pino

#load common script and check programs
. common_script.sh
check_program hugs

cat common_script.sh > vpl_execution
echo "runhugs +98 $VPL_SUBFILE0" >>vpl_execution
chmod +x vpl_execution
