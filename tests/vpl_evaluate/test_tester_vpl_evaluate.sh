#!/bin/bash
CHECK_MARK="\u2713";
X_MARK="\u274C";
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
	cd $TESTDIR
	if [ ! -x vpl_execution ] ; then
	    writeError "$X_MARK"
		writeError "Test $1 failed: evaluation program compilation failed"
		cat $VPLTESTERRORS
		result=1
	else
		mark=$(grep -E 'Grade :=>>' "$VPLTESTOUTPUT" | tail -n1 | sed 's/Grade :=>> *//i')
		if [ "$mark" != "$VPL_GRADEMAX" ] ; then
		    writeError "$X_MARK"
			result=1
			if [ -s "$VPLTESTERRORS" ] ; then
			    echo "The program has generated the following errors"
			    cat $VPLTESTERRORS
			else
		    	cat "$VPLTESTOUTPUT"
			fi
		fi
	fi
	if [ "$mark" == "$VPL_GRADEMAX" ] ; then
	    writeInfo "$CHECK_MARK"
	fi
    rm -Rf $TESTDIR
    return $result
}

function runAllTests {
	local ntests=0
	local npass=0
    local finalResult=0
	cd $CASESDIR
	local cases=$(ls -d *.cases | sed 's/\.cases$//')
	for case in $cases
	do
		let ntests=ntests+1
		initTest $case
		runTest $case
		evalTest $case
		if [ "$?" != "0" ] ; then
			finalResult=1
		else
			let npass=npass+1
		fi
	done
	if [ "$npass" == "$ntests" ] ; then
		echo -n "OK "
	else
		echo -n "Fail "
	fi
	echo "$npass of $ntests tests passed"
	return $finalResult
}
OLDDIR="$(pwd)"
cd $(dirname $0)
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
cd $OLDDIR
exit $finalResult
