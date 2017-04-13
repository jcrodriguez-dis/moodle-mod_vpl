#!/bin/bash
function initTest {
	mkdir $TESTDIR
	cp $ORIGINDIR/vpl_evaluate.cpp $TESTDIR
	cp $ORIGINDIR/default_evaluate.sh $TESTDIR
	cp $ORIGINDIR/commond_script.sh $TESTDIR
	cp $CASESDIR/vpl_run$1.sh $TESTDIR/vpl_run.sh
	cp $CASESDIR/vpl_evaluate$1.cases $TESTDIR/vpl_evaluate.cases
	cp $CASESDIR/vpl_test_evaluate$1.sh $TESTDIR/vpl_test_evaluate.sh
}

function runTest {
	cd $TESTDIR
	chmod +x *.sh
	./default_evaluate.sh
	if [ ! -s vpl_execution ] ; then
		echo "Test $1 failed: evaluation program compilation failed"
		cd ..
		exit 1
	else
		./vpl_execution
	fi
    cd ..
}

function evalTest {
	
}

function runAllTests {
	local cases="$(find $CASESDIR -name "vpl_run*.sh" -print | sed 's/^vpl_run\///g' | sed 's/.sh$\///g' )"
	for case in $cases
	do
		initTest $case
		runTest $case
		evalTest $case
	done
}

echo "Testing default Student's program tester of VPL for Moodle"
ORIGINDIR="../../jail/default_scripts"
TESTDIR="test"
CASESDIR="cases"
runAllTests


