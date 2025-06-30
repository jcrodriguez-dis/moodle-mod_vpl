#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running PHP language using the PHP Built-in web server
# Copyright (C) 2025 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using "External Python Tutor (Python, JavaScript, C, C++, and Java)"

# load common script and check programs
. common_script.sh

check_program php php5
PHP=$PROGRAM
if [ "$1" == "version" ] ; then
	get_program_version -v
fi

get_first_source_file py js c java cpp C c++

if [ "$FIRST_SOURCE_FILE" != "" ] ; then
    PHPCONFIGFILE=$($PHP -i 2>/dev/null | grep "Loaded Configuration File" | sed 's/^[^\/]*//' )
    if [ "$PHPCONFIGFILE" == "" ] ; then
    	touch .php.ini
    else
    	cp $PHPCONFIGFILE .php.ini	
    fi
    #Configure session
    SESSIONPATH=$HOME/.php_sessions
    mkdir $SESSIONPATH &> /dev/null
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
$filename = getenv('FIRST_SOURCE_FILE');
if($filename == false || !is_file($filename)) {
  return false;
}

$ext2lang = [
    'py' => '3',
    'c' => 'c',
    'java' => 'java',
    'cpp' => 'cpp',
    'C' => 'cpp',
    'c++' => 'cpp',
    'js' => 'js'
];
$file_ext = getenv('FILE_EXT');
$filecontent = file_get_contents($filename);
$lang = $ext2lang[$file_ext];

$params = [
    "code" => $filecontent,
    "cumulative" => "false",
    "heapPrimitives" => "nevernest",
    "mode" => "display",
    "origin" => "opt-frontend.js",
    "py" => $lang,
    "rawInputLstJSON" => "[]",
    "textReferences" => "false"
];

$query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
$URL = "https://pythontutor.com/iframe-embed.html#{$query}";
if (getenv('VPL_USING_SEB') == '1') {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Python Tutor + VPL</title>
    <style>
        body {
            padding: 3px;
        }
        iframe {
            width: 100%;
            height: 600px;
            border: 1px solid #00a;
        }
    </style>
</head>
<body>
    <iframe src="<?php echo $URL; ?>"></iframe>
</body>
</html>
<?php
}else{
    header("Location: $URL");
}
touch('.vpl_stop_server');

END_OF_PHP
    # Calculate IP 127.X.X.X: (random port)
    if [ "$UID" == "" ] ; then
    	echo "Error: UID not set"
    fi
    if [ -f .vpl_using_seb ] ; then
        export VPL_USING_SEB=1
    fi
    export serverPort=$((10000+$RANDOM%50000))
    export serverIP="127.$((1+$UID/1024%64)).$((1+$UID/16%64)).$((10+$UID%16))"
    echo "$serverIP:$serverPort" > .vpl_localserveraddress
    cat common_script.sh > vpl_webexecution
    cat >> vpl_webexecution <<END_OF_SCRIPT
#!/bin/bash
export FILE_EXT="${FIRST_SOURCE_FILE##*.}"
export FIRST_SOURCE_FILE=$FIRST_SOURCE_FILE
export VPL_USING_SEB=$VPL_USING_SEB
$PHP -c .php.ini -S "$serverIP:$serverPort" .router.php &>/dev/null &
PHP_SERVER_PID=$!
while true; do
    sleep 1
    echo -n .
    if [ -f .vpl_stop_server ] ; then
        exit 0
    fi
done
END_OF_SCRIPT
    chmod +x vpl_webexecution
fi
