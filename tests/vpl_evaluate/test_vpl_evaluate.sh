#!/bin/bash
CHECK_MARK="\u2713";
X_MARK="\u274C";
function writeHeading {
	echo -e "\e[37;42m RUN \e[39;49m \e[34m$1\e[39m"
}
function info {
	echo -e "\e[42m$1\e[0m"
}
function error {
	echo -e "\e[31m$1\e[0m"
}
function write {
	echo -e "$1"
}
function initTest {
	info "Test: $1"
	if [ -s "$TESTDIR" ] ; then
		rm -Rf "$TESTDIR"
	fi
	mkdir $TESTDIR
	cp $ORIGINDIR/vpl_evaluate.cpp $TESTDIR
	cp $ORIGINDIR/default_evaluate.sh $TESTDIR
	cp $ORIGINDIR/common_script.sh $TESTDIR
	cp $CASESDIR/$1_vpl_run.sh $TESTDIR/vpl_run.sh
	cp $CASESDIR/$1_vpl_evaluate.cases $TESTDIR/vpl_evaluate.cases
	cp $CASESDIR/$1_vpl_test_evaluate.sh $TESTDIR/vpl_test_evaluate.sh
	cat > $TESTDIR/vpl_environment.sh << ENDOFSCRIPT
#!/bin/bash

ENDOFSCRIPT

}

function runTest {
	cd $TESTDIR
	chmod +x *.sh
	./default_evaluate.sh
	if [ ! -s vpl_execution ] ; then
		error "Test $1 failed: evaluation program compilation failed"
		cd ..
		exit 1
	else
		. common_script.sh
		./vpl_execution > "$VPLTESTOUTPUT" 2> "$VPLTESTERRORS"
		VPL_GRADEMIN=0
		VPL_GRADEMAX=10
	fi
    cd ..
}

function evalTest {
	cd $TESTDIR
	./vpl_test_evaluate.sh $1
	local result=$?
    cd ..
    rm -Rf $TESTDIR
    return $result
}

function runAllTests {
    local finalResult=0
	local cases=$(find $CASESDIR -name "*_vpl_run.sh" -print | sort | sed "s/^$CASESDIR\///g" | sed 's/\_vpl_run.sh$//g' )
	for case in $cases
	do
		initTest $case
		runTest $case
		evalTest $case
		if [ "$?" != "0" ] ; then
			finalResult=1
		fi
	done
	return $finalResult
}

writeHeading "TDSPT Testing default Student's program tester of VPL for Moodle"
export ORIGINDIR="../../jail/default_scripts"
export TESTDIR="test"
export CASESDIR="cases"
export VPLTESTOUTPUT=".vpl_test_output"
export VPLTESTERRORS=".vpl_test_errors"

runAllTests
