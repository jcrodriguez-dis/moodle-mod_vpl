#!/bin/bash
# This file is part of VPL for Moodle
# Ruby language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.rb <<'END_OF_FILE'
print "Hello from the Ruby language!\n"
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.rb
