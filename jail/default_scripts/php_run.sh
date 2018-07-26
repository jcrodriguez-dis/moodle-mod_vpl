#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running PHP language
# Copyright (C) 2012 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "php -n -f" with the first file or on serve if index.php exists
# load common script and check programs
. common_script.sh
check_program php5 php
PHP=$PROGRAM
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "$PHP -v" >> vpl_execution
	chmod +x vpl_execution
	exit
fi
check_program x-www-browser firefox
BROWSER=$PROGRAM
if [ -f index.php ] ; then
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
<?php $path=urldecode(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH));
$file='.'.$path;
if(is_file($file) || is_file($file.'/index.php') || is_file($file.'/index.html') ){
    unset($path,$file);
    return false;
}
$pclean=htmlentities($path);
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
while true; do
   	PHPPORT=$((6000+$RANDOM%25000))
   	netstat -tln | grep -q ":$PHPPORT "
   	[ "$?" != "0" ] && break
done
cat > vpl_wexecution <<END_OF_SCRIPT
#!/bin/bash
$PHP -c .php.ini -S "127.0.0.1:$PHPPORT" .router.php &
$BROWSER "127.0.0.1:$PHPPORT"
END_OF_SCRIPT
    chmod +x vpl_wexecution
else
	get_first_source_file php
    cat common_script.sh > vpl_execution
    echo "$PHP -n -f $FIRST_SOURCE_FILE \$@" >>vpl_execution
    chmod +x vpl_execution
fi
