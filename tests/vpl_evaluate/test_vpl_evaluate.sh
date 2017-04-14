#!/bin/bash
function writeHeading {
	echo "<bg=green;fg=white;> RUN </> <fg=blue>$1</>"
}
function write {
	echo "<info>$1</info>"
}
function initTest {
	mkdir $TESTDIR
	cp $ORIGINDIR/vpl_evaluate.cpp $TESTDIR
	cp $ORIGINDIR/default_evaluate.sh $TESTDIR
	cp $ORIGINDIR/commond_script.sh $TESTDIR
	cp $CASESDIR/vpl_run_$1.sh $TESTDIR/vpl_run.sh
	cp $CASESDIR/vpl_evaluate_$1.cases $TESTDIR/vpl_evaluate.cases
	cp $CASESDIR/vpl_test_evaluate_$1.sh $TESTDIR/vpl_test_evaluate.sh
}

function runTest {
	write "Test: $1"
	cd $TESTDIR
	chmod +x *.sh
	./default_evaluate.sh
	if [ ! -s vpl_execution ] ; then
		write "Test $1 failed: evaluation program compilation failed"
		cd ..
		exit 1
	else
		./vpl_execution
	fi
    cd ..
}

function evalTest {
	cd $TESTDIR
	./vpl_test_evaluate
	local RET=$?
    cd ..
    return $RET
}

function runAllTests {
	local cases="$(find $CASESDIR -name "vpl_run_*.sh" -print | sed 's/^vpl_run_\///g' | sed 's/\.sh$\///g' )"
	for case in $cases
	do
		initTest $case
		runTest $case
		evalTest $case
	done
}
writeHeading "TDSPT"
write "Testing default Student's program tester of VPL for Moodle"
ORIGINDIR="../../jail/default_scripts"
TESTDIR="test"
CASESDIR="cases"
runAllTests


