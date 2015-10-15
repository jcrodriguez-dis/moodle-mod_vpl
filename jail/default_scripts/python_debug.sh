#!/bin/bash
# $Id: python_debug.sh,v 1.4 2012-09-24 15:13:21 juanca Exp $
# Default Python language debug script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program python
cat common_script.sh > vpl_execution
echo "python -m pdb $VPL_SUBFILE0" >>vpl_execution
chmod +x vpl_execution
