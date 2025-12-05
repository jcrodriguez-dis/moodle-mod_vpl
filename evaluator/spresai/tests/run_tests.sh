#!/bin/bash
# This file is part of VPL for Moodle
# Script for test GIOTES for VPL
# Copyright (C) 2025 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
# This script is used to test the GIOTES plugin for VPL
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

export -f writeHeading
export -f writeInfo
export -f writeError
export -f write

function initTest {
	mkdir "$TESTDIR"
	mkdir "$TESTDIR/spresai"
	cp "$ORIGINDIR/"*.py "$TESTDIR/spresai"
	cp "$ORIGINDIR/"*.txt "$TESTDIR/spresai"
	cp "$BASEDIR/"*.py "$TESTDIR/spresai"
	cp "$ORIGINDIR/vpl_evaluate.sh" "$TESTDIR"
	cp "$COMMONSCRIPTSDIR/common_script.sh" "$TESTDIR"
	cat > "$TESTDIR/vpl_environment.sh" << ENDOFSCRIPT
#!/bin/bash
export VPL_GRADEMIN=0
export VPL_GRADEMAX=10
export VPL_MAXTIME=20
export VPL_VARIATION=
ENDOFSCRIPT
}

function endTest {
	rm -rf "$TESTDIR"
}


function runTest {
	initTest
	cd "$TESTDIR"
	python3 spresai/spresai_test.py
	local result=$?
    cd ..
	endTest
	return $result
}

OLDDIR=$(pwd)
BASEDIR="$(cd "$(dirname "$0")" && pwd)"
cd "$BASEDIR"
export ORIGINDIR="$BASEDIR/../src"
export COMMONSCRIPTSDIR="$BASEDIR/../../../jail/default_scripts"
export TESTDIR="vpl_test.test"

writeHeading "Testing SPRESAI: plugin for evaluating student's submissions on VPL for Moodle"
writeHeading "with AI models using LiteLLM"

runTest

finalResult=$?
cd "$OLDDIR"
exit $finalResult
