#!/bin/bash
# This file is part of VPL for Moodle
# Script for test SPRESAI for VPL
# Copyright (C) 2025 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
# This script is used to test the SPRESAI plugin for VPL
# It runs the test cases and evaluates the results

CHECK_MARK="✅";
X_MARK="❌";
function writeHeading {
    echo -e "\e[37;42m RUN \e[39;49m \e[34m$1\e[39m"
}
function writeInfo {
    echo -e $3 "\e[42m$1\e[0m$2"
}
function writeError {
    echo -e "\e[31m$1\e[0m$2"
}
function write {
    echo -e "$1"
}

function writeDebug {
	if [ "$VPL_DEBUG" = "1" ]; then
		writeInfo "$1" "$2"
	fi
}

export -f writeHeading
export -f writeInfo
export -f writeError
export -f write

function initTest {
    writeDebug "Initializing test environment in $TESTDIR"
    # Ensure clean state
    if [ -d "$TESTDIR" ]; then
        rm -rf "$TESTDIR"
    fi
    mkdir "$TESTDIR"
    mkdir "$TESTDIR/spresai"
    
	cp "$ORIGINDIR/"*.py "$TESTDIR/spresai"
	cp "$ORIGINDIR/"*.txt "$TESTDIR/spresai"
	cp "$ORIGINDIR/vpl_evaluate.sh" "$TESTDIR"
    cp "$BASEDIR/"*.py "$TESTDIR/spresai"
    cp "$COMMONSCRIPTSDIR/common_script.sh" "$TESTDIR"
    cat > "$TESTDIR/vpl_environment.sh" << ENDOFSCRIPT
#!/bin/bash
export VPL_GRADEMIN=0
export VPL_GRADEMAX=10
export VPL_MAXTIME=20
export VPL_VARIATION=
ENDOFSCRIPT
    chmod +x "$TESTDIR/vpl_environment.sh"
}

function endTest {
	writeDebug "Cleaning up test environment in $TESTDIR"
	if [ "$TESTDIR" != "" ] && [ -d "$TESTDIR" ]; then
	    rm -rf "$TESTDIR"
	fi
}

# Ensure cleanup happens on exit or interrupt
trap endTest EXIT

function runTest {
    initTest
    cd "$TESTDIR"
    python3 spresai/spresai_tests.py
    local result=$?
    cd ..
	endTest
    return $result
}

OLDDIR=$(pwd)
BASEDIR="$(cd "$(dirname "$0")" && pwd)"
writeHeading "Testing SPRESAI: plugin for evaluating with AI student's submissions on VPL for Moodle"

cd "$BASEDIR"
export ORIGINDIR="$BASEDIR/../src"
export COMMONSCRIPTSDIR="$BASEDIR/../../../jail/default_scripts"
export TESTDIR="vpl_test.test"
writeDebug "Base directory: " "$BASEDIR"
writeDebug "Origin directory: " "$ORIGINDIR"
writeDebug "Common scripts directory: " "$COMMONSCRIPTSDIR"
writeDebug "Test directory: " "$TESTDIR"

runTest
finalResult=$?
if [ $finalResult -eq 0 ]; then
    writeInfo "All tests passed " "$CHECK_MARK"
else
    writeError "Some tests failed " "$X_MARK"
fi
cd "$OLDDIR"
exit $finalResult
