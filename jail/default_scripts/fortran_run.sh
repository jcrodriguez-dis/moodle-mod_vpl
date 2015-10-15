#!/bin/bash
# $Id: fortran_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# Default Fortran language run script for VPL
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load VPL environment vars
. common_script.sh
check_program gfortran
get_source_files f f77
#compile
gfortran -o vpl_execution $SOURCE_FILES
