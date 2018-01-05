#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Haskell language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using "hugs +98" with the first file
# load common script and check programs
. common_script.sh
check_program hugs

cat common_script.sh > vpl_execution
echo "hugs +98 $VPL_SUBFILE0" >>vpl_execution
chmod +x vpl_execution
