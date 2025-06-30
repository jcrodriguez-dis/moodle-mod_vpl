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

function assertOutput {
	grep -e "$1" "$VPLTESTOUTPUT" >/dev/null
	[ $? -eq 0 ] && return 0
	write
	writeError "Not found: " "\"$1\" not found in output result"
	exit 1
}

function assertOutputFalse {
	grep -e "$1" "$VPLTESTOUTPUT" >/dev/null
	[ $? -ne 0 ] && return 0
	write
	writeError "Found: " "\"$1\" found in output result"
	exit 1
}

function assertErrors {
	grep -e "$1" "$VPLTESTERRORS" >/dev/null
	[ $? -eq 0 ] && return 0
	write
	writeError "Not found: " "\"$1\" not found in output errors (stderr)"
	exit 1
}

export -f assertOutput
export -f assertOutputFalse
export -f assertErrors

function initTest {
	writeInfo "Test" ": $1 " -n
	if [ -d "$TESTDIR" ] ; then
		rm -Rf "$TESTDIR"
	fi
	mkdir "$TESTDIR"
	mkdir "$TESTDIR/.giotes"
	cp "$ORIGINDIR/"*.cpp "$TESTDIR/.giotes"
	cp "$ORIGINDIR/"*.hpp "$TESTDIR/.giotes"
	cp "$ORIGINDIR/vpl_evaluate.sh" "$TESTDIR"
	cp "$COMMONSCRIPTSDIR/common_script.sh" "$TESTDIR"
	cp "$CASESDIR/$1_vpl_run.sh" "$TESTDIR/vpl_run.sh"
	cp "$CASESDIR/$1_vpl_evaluate.cases" "$TESTDIR/vpl_evaluate.cases"
	cp "$CASESDIR/$1_vpl_test_evaluate.sh" "$TESTDIR/vpl_test_evaluate.sh"
	cat > "$TESTDIR/vpl_environment.sh" << ENDOFSCRIPT
#!/bin/bash
export VPL_GRADEMIN=0
export VPL_GRADEMAX=10
export VPL_MAXTIME=20
export VPL_VARIATION=

ENDOFSCRIPT

}

function runTest {
	cd "$TESTDIR"
	chmod +x *.sh
	./vpl_evaluate.sh
	if [ -s vpl_execution ] ; then
		. common_script.sh
		command -v valgrind > /dev/null
		if [ "$?" != "0" -o "$VPL_VALGRIND" = "" ]  ; then
			./vpl_execution > "$VPLTESTOUTPUT" 2> "$VPLTESTERRORS"
		else
			valgrind --tool=memcheck --track-origins=yes ./vpl_execution > "$VPLTESTOUTPUT" 2> "$VPLTESTERRORS"
			if [ -s "$VPLTESTERRORS" -a "$RUNGDB" != "" ] ; then
				echo "run" | gdb ./.vpl_tester
			fi
		fi
		VPL_GRADEMIN=0
		VPL_GRADEMAX=10
		VPL_MAXTIME=20
		VPL_VARIATION=
		VPL_VALGRIND=
	fi
    cd ..
}

function evalTest {
	local result=0
	cd $TESTDIR
	if [ ! -s vpl_execution ] ; then
	    writeError "$X_MARK"
		writeError "Test $1 failed: evaluation program compilation failed"
		result=1
	else
		./vpl_test_evaluate.sh "$1"
		result=$?
		if [ "$result" != "0" ] ; then
		    writeError "$X_MARK"
			if [ -s "$VPLTESTERRORS" ] ; then
			    echo "The program has generated the following errors"
			    cat "$VPLTESTERRORS"
			fi
			cat "$VPLTESTOUTPUT"
		elif [ -n "$VPL_DEBUG" ] ; then
			echo "OUTPUT Testing $1"
			[ -s "$VPLTESTERRORS" ] && cat "$VPLTESTERRORS"
			cat "$VPLTESTOUTPUT"
		fi
	fi
	if [ "$result" = "0" ] ; then
	    writeInfo "$CHECK_MARK"
	fi
    cd ..
    rm -Rf $TESTDIR
    return $result
}

function runAllTests {
	local ntests=0
	local npass=0
    local finalResult=0
	local cases=$(find $CASESDIR -name "*_vpl_run.sh" -print | sort | sed "s/^$CASESDIR\///g" | sed 's/\_vpl_run.sh$//g' )
	[ -n "$1" ] && cases=$1
	for case in $cases
	do
		let ntests=ntests+1
		initTest "$case"
		runTest "$case"
		evalTest "$case"
		if [ "$?" != "0" ] ; then
			finalResult=1
		else
			let npass=npass+1
		fi
	done
	if [ "$npass" = "$ntests" ] ; then
		echo -n "OK "
	else
		echo -n "Fail "
	fi
	echo "$npass/$ntests tests passed"
	return $finalResult
}

# Main
OLDDIR=$(pwd)
BASEDIR="$(cd "$(dirname "$0")" && pwd)"
cd "$BASEDIR"
writeHeading "Testing GIOTES: plugin for evaluating student's submissions on VPL for Moodle"
export ORIGINDIR="$BASEDIR/../src"
export COMMONSCRIPTSDIR="$BASEDIR/../../../jail/default_scripts"
export TESTDIR="vpl_test.test"
export CASESDIR="cases"
export VPLTESTOUTPUT=".vpl_test_output"
export VPLTESTERRORS=".vpl_test_errors"
[[ "$2" = "DEBUG" ]] && export VPL_DEBUG=Yes
if [[ "$1" = "DEBUG" ]] ; then
	shift
	export VPL_DEBUG=Yes
fi
runAllTests "$1"
finalResult=$?
cd "$OLDDIR"
exit $finalResult
