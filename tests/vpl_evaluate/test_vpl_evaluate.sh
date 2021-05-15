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
	writeInfo "Test" ": $1 " -n
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
export VPL_GRADEMIN=0
export VPL_GRADEMAX=10
export VPL_MAXTIME=20
export VPL_VARIATION=

ENDOFSCRIPT

}

function runTest {
	cd $TESTDIR
	chmod +x *.sh
	./default_evaluate.sh
	if [ -s vpl_execution ] ; then
		. common_script.sh
		command -v valgrind > /dev/null
		if [ "$?" != "0" ] || [ "$VPL_VALGRIND" == "" ] ; then
			./vpl_execution > "$VPLTESTOUTPUT" 2> "$VPLTESTERRORS"
		else
			valgrind --tool=memcheck ./vpl_execution > "$VPLTESTOUTPUT" 2> "$VPLTESTERRORS"
		fi
		VPL_VALGRIND=
		VPL_GRADEMIN=0
		VPL_GRADEMAX=10
		VPL_MAXTIME=20
		VPL_VARIATION=
	fi
    cd ..
}

function evalTest {
	local result=0
	cd $TESTDIR
	if [ ! -s vpl_execution ] ; then
	    writeError "$X_MARK"
    	echo "travis_fold:start:vpl_test.$1"
		writeError "Test $1 failed: evaluation program compilation failed"
    	echo "travis_fold:end:vpl_test.$1"
		result=1
	else
		./vpl_test_evaluate.sh $1
		result=$?
		if [ "$result" != "0" ] ; then
		    writeError "$X_MARK"
	    	echo "travis_fold:start:vpl_test.$1"
			if [ -s "$VPLTESTERRORS" ] ; then
			    echo "The program has generated the following errors"
			    cat $VPLTESTERRORS
			else
		    	cat "$VPLTESTOUTPUT"
			fi
	    	echo "travis_fold:end:vpl_test.$1"
		fi
	fi
	if [ "$result" == "0" ] ; then
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
	echo "$npass/$ntests tests passed"
	return $finalResult
}
OLDDIR=$(pwd)
cd $(dirname $0)
writeHeading "TDSPT Testing default Student's program tester of VPL for Moodle"
export ORIGINDIR="../../jail/default_scripts"
export TESTDIR="test"
export CASESDIR="cases"
export VPLTESTOUTPUT=".vpl_test_output"
export VPLTESTERRORS=".vpl_test_errors"

runAllTests
cd $OLDDIR
