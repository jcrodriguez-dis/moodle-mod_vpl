#!/bin/bash
# This file is part of VPL for Moodle
# Default evaluate script for VPL
# Copyright (C) 2024 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

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
	cd "$CASESDIR"
	writeInfo "Test" ": $1 " -n
	if [ -s "$TESTDIR" ] ; then
		rm -Rf "$TESTDIR"
	fi
	mkdir "$TESTDIR"
	cp "$ORIGINDIR/vpl_evaluate.cpp" "$TESTDIR"
	cp "$ORIGINDIR/default_evaluate.sh" "$TESTDIR/vpl_evaluate.sh"
	cp "$ORIGINDIR/default_test_evaluate.sh" "$TESTDIR/vpl_test_evaluate.sh"
	cp "$ORIGINDIR/common_script.sh" "$TESTDIR"
	mkdir "$TESTDIR/vpl_evaluation_tests"
	cp -a $1.cases/* "$TESTDIR/vpl_evaluation_tests"
	cp -a $1.data/* "$TESTDIR"
	chmod +x "$TESTDIR/"*
}

function runTest {
	cd "$TESTDIR"
	touch "$VPLTESTOUTPUT"
	touch "$VPLTESTERRORS"
	chmod +x *.sh
	./vpl_test_evaluate.sh >> "$VPLTESTOUTPUT" 2>> "$VPLTESTERRORS"
	if [ -x vpl_execution ] ; then
		./vpl_execution >> "$VPLTESTOUTPUT" 2>> "$VPLTESTERRORS"
	fi
}

function evalTest {
	local result=0
	local mark=''
	cd "$TESTDIR"
	if [ ! -x vpl_execution ] ; then
	    write "$X_MARK"
		write "Test $1 failed: evaluation program compilation failed" >2
		cat "$VPLTESTERRORS" >2
		result=1
	else
		mark=$(grep -E 'Grade :=>>' "$VPLTESTOUTPUT" | tail -n1 | sed 's/Grade :=>> *//i')
		if [ "$mark" != "$VPL_GRADEMAX" ] ; then
		    write "$X_MARK"
			result=1
			if [ -s "$VPLTESTERRORS" ] ; then
			    echo "The program has generated the following errors" >2
			    cat "$VPLTESTERRORS" >2
			else
		    	cat "$VPLTESTOUTPUT" >2
			fi
		fi
	fi
	if [ "$mark" == "$VPL_GRADEMAX" ] ; then
	    write "$CHECK_MARK"
	fi
    rm -Rf "$TESTDIR"
    return $result
}

function runAllTests {
	local test_errors="$CASESDIR/.test_errors.txt"
	local ntests=0
	local npass=0
    local finalResult=0
	local expectedResult="0"
	cd "$CASESDIR"
	local cases=$(ls -d *.cases | sed 's/\.cases$//')
	for case in $cases
	do
		if [ "$(echo "$case" | sed 's/fail.*$//')" == "" ] ; then
			expectedResult="1"
		else
			expectedResult="0"
		fi
		let ntests=ntests+1
		initTest "$case"
		runTest "$case"
		evalTest "$case" 2>> "$test_errors"
		if [ "$?" != "$expectedResult" ] ; then
			echo "   ➡ $X_MARK"
			finalResult=1
		else
			echo "   ➡ $CHECK_MARK"
			let npass=npass+1
		fi
	done
	if [ "$npass" == "$ntests" ] ; then
		echo -n "OK all "
	else
		echo
		cat "$test_errors"
		echo -n "Fail only "
	fi
	echo "$npass of $ntests tests passed"
	return $finalResult
}
INIDIR="$(pwd)"
cd "$(dirname $0)"
OLDDIR="$(pwd)"
writeHeading "Testing automatic program test for VPL for Moodle"
export ORIGINDIR="$OLDDIR/../../jail/default_scripts"
export TESTDIR="$OLDDIR/vpl_test.test"
export CASESDIR="$OLDDIR/tester_cases"
export VPLTESTOUTPUT="$TESTDIR/.vpl_test_output"
export VPLTESTERRORS="$TESTDIR/.vpl_test_errors"
export VPL_GRADEMAX="10"
cd $CASESDIR
runAllTests
finalResult=$?
cd $INIDIR
exit $finalResult
