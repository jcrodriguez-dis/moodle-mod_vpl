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
require_once "test php/message hello.php";
echo "<h1>";
hello();
echo "</h1>";
phpinfo();
?>
</body>
</html>
END_OF_FILE

export VPL_SUBFILE0="index.php"

else

cat > "vpl hello.php" <<'END_OF_FILE'
<?php
require_once "test php/message hello.php";
hello();
END_OF_FILE

export VPL_SUBFILE0="vpl hello.php"
fi

mkdir "test php" 2> /dev/null

cat > "test php/message hello.php" <<'END_OF_FILE'
<?php
function hello() {
	$text = readline();
	echo "$text\n";
}
END_OF_FILE

export VPL_SUBFILE1="test php/message hello.php"
export INPUT_TEXT="Hello from the PHP language!"
