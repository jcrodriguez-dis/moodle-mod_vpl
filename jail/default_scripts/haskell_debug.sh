#!/bin/bash
# This file is part of VPL for Moodle
# Script for debugging Haskell language
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

# @vpl_script_description Using "hugs +98" with the first file
# load common script and check programs
. common_script.sh

check_program ghc hugs
if [ "$1" == "version" ] ; then
	exit
fi

cat common_script.sh > vpl_execution

get_first_source_file hs lhs
if [ "$PROGRAM" == "hugs" ] ; then
	echo "runhugs +98 \"$FIRST_SOURCE_FILE\" \$@" >>vpl_execution
else
	$PROGRAMPATH -o ghc_execution -prof -fprof-auto -fprof-cafs "$FIRST_SOURCE_FILE"
	echo "./ghc_execution +RTS -xc \$@" >>vpl_execution
fi
chmod +x vpl_execution
