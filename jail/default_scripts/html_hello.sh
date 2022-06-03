#!/bin/bash
# This file is part of VPL for Moodle
# HTML markup language Hello  code
# Copyright 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.html" <<"END_OF_FILE"
<!DOCTYPE html>
<html><head><title>Hello from VPL</title></head>
<body>
<h1>Hello from the HTML language!</h1>
</body>
</html>
END_OF_FILE
export VPL_SUBFILE0="vpl hello.html"
