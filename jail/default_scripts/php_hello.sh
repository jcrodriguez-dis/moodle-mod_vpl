#!/bin/bash
# This file is part of VPL for Moodle
# PHP language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
cat >index.php <<"END_OF_FILE"

<!DOCTYPE html>
<html><head><title>VPL</title></head>
<body>
<?php
echo "<h1>Hello from the PHP language!</h1>";
phpinfo();
?>
</body>
</html>
END_OF_FILE
export VPL_SUBFILE0=index.php
else
cat >vpl_hello.php <<'END_OF_FILE'
<?php
echo "Hello from the PHP language!\n";
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.php
fi
