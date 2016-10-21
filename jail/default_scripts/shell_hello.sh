#!/bin/bash
# This file is part of VPL for Moodle
# Shell (Bash) language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.sh <<'END_OF_FILE'
#!/bin/bash
echo "Hello from the Shell (Bash) language!"
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.sh
