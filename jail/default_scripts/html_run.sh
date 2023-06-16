#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running HTML using the PHP Built-in web server
# Copyright (C) 2022 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "php -n -f" access index.html

# load common script and check programs
. common_script.sh

check_program php php5
PHP=$PROGRAM
if [ "$1" == "version" ] ; then
	get_program_version -v
fi

compile_typescript
compile_scss
PHPCONFIGFILE=$($PHP -i 2>/dev/null | grep "Loaded Configuration File" | sed 's/^[^\/]*//' )
if [ "$PHPCONFIGFILE" == "" ] ; then
	touch .php.ini
else
	cp $PHPCONFIGFILE .php.ini	
fi
#Configure session
SESSIONPATH=$HOME/.php_sessions
mkdir $SESSIONPATH
#Generate php.ini
cat >> .php.ini <<END_OF_INI
	
session.save_path="$SESSIONPATH"
error_reporting=E_ALL
display_errors=On
display_startup_errors=On
END_OF_INI

#Generate router
cat >> .router.php << 'END_OF_PHP'
<?php
$path = urldecode(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH));
$file = '.' . $path;
if(is_file($file) ||
   is_file($file . '/index.php') ||
   is_file($file . '/index.html') ){
      unset($path, $file);
      return false;
}
$pclean = htmlentities($path);
http_response_code(404);
header(':', true, 404);
?>
<!doctype html>
<html><head><title>404 Not found</title>
<style>h1{background-color: aqua;text-align:center} code{font-size:150%}</style>
</head>
<body><h1>404 Not found</h1><p>The requested resource <code><?php echo "'$pclean'"; ?></code> 
was not found on this server</body></html>
END_OF_PHP
# Calculate IP 127.X.X.X: (random port)
if [ "$UID" == "" ] ; then
	echo "Error: UID not set"
fi
export serverPort=$((10000+$RANDOM%50000))
export serverIP="127.$((1+$UID/1024%64)).$((1+$UID/16%64)).$((10+$UID%16))"
echo "$serverIP:$serverPort" > .vpl_localserveraddress
cat common_script.sh > vpl_webexecution
cat >> vpl_webexecution <<END_OF_SCRIPT
#!/bin/bash
$PHP -c .php.ini -S "$serverIP:$serverPort" .router.php
END_OF_SCRIPT
chmod +x vpl_webexecution
