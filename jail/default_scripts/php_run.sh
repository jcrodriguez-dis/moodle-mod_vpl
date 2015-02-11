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
	SESSIONPATH=$HOME/.php_sessions
	mkdir $SESSIONPATH
	cp /etc/php5/cli/php.ini .php.ini
	cat >> .php.ini <<FIN
	
session.save_path ="$SESSIONPATH"

FIN
    cat > vpl_wexecution <<"END_OF_SCRIPT"
#!/bin/bash
while true; do
   	PHPPORT=$((6000+$RANDOM%25000))
   	lsof -i :$PHPPORT
   	[ "$?" != "0" ] && break
done
php -c .php.ini -d display_errors=On -S "127.0.0.1:$PHPPORT" &
x-www-browser "127.0.0.1:$PHPPORT"
END_OF_SCRIPT
    chmod +x vpl_wexecution
else
    cat common_script.sh > vpl_execution
    echo "php -n -f $VPL_SUBFILE0" >>vpl_execution
    chmod +x vpl_execution
fi
