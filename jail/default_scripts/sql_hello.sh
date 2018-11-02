#!/bin/bash
# This file is part of VPL for Moodle
# SQL language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.sql" <<'END_OF_FILE'

END_OF_FILE

mkdir "test sql" 2> /dev/null

cat >"test sql/vpl message.sql" <<'END_OF_FILE'
SELECT 'Hello from the SQL language!';
END_OF_FILE

export VPL_SUBFILE0="vpl hello.sql"
export VPL_SUBFILE1="test sql/vpl message.sql"
