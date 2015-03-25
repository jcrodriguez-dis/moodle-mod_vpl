#!/bin/bash
# Default PHP language run script for VPL
# Copyright (C) 2012 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

#load common script and check programs
. common_script.sh
check_program php5
check_program x-www-browser
if [ -f index.php ] ; then
    #Configure session
	SESSIONPATH=$HOME/.php_sessions
	mkdir $SESSIONPATH
	cp /etc/php5/cli/php.ini .php.ini
	#Generate php.ini
	cat >> .php.ini <<END_OF_INI
	
session.save_path="$SESSIONPATH"
error_reporting=E_ALL
display_errors=On
display_startup_errors=On
END_OF_INI
    #Generate router
    cat >> .router.php << 'END_OF_PHP'
<?php $path='.'.urldecode(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH));
if(is_file($path) || is_file($path.'/index.php') || is_file($path.'/index.html') ){
    unset($path);
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
    cat > vpl_wexecution <<"END_OF_SCRIPT"
#!/bin/bash
while true; do
   	PHPPORT=$((6000+$RANDOM%25000))
   	lsof -i :$PHPPORT
   	[ "$?" != "0" ] && break
done
php -c .php.ini -S "127.0.0.1:$PHPPORT" .router.php &
x-www-browser "127.0.0.1:$PHPPORT"
END_OF_SCRIPT
    chmod +x vpl_wexecution
else
    cat common_script.sh > vpl_execution
    echo "php -n -f $VPL_SUBFILE0" >>vpl_execution
    chmod +x vpl_execution
fi
