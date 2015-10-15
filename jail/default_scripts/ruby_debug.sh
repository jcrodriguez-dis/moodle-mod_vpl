#!/bin/bash
# $Id: ruby_debug.sh,v 1.4 2012-09-24 15:13:22 juanca Exp $
# Default Ruby language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program ruby
cat common_script.sh > vpl_execution
echo "ruby -rdebug $VPL_SUBFILE0" >>vpl_execution
chmod +x vpl_execution
