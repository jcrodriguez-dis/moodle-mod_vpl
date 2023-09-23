#!/bin/bash
# This file is part of VPL for Moodle
# Default evaluate script for VPL
# Copyright (C) 2023 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# Load VPL environment vars.
. common_script.sh

check_program awk
if [ "$VPL_MAXTIME" = "" ] ; then
	export VPL_MAXTIME=20
fi
let VPL_MAXTIME=$VPL_MAXTIME+5;
if [ "$VPL_GRADEMAX" == "" ] ; then
	export VPL_GRADEMIN=0
	export VPL_GRADEMAX=10
	echo "Note: Using default grade marks 0..10"
fi

# Globals vars and initial actions
export star_symbol='⭐'
export pass_symbol='✅'
export fail_symbol='❌'
export bug_symbol='🐞'
export ERRORS=''
export number_format="([0-9]+([.][0-9]*)?|[.][0-9]+)"
export directory_format="/(pass|fail)- *([^-]*) *(-$number_format)?$"
export home_dir=$(pwd)
export oldtest_dir='vpl_evaluation_tests'
export test_dir='.vpl_evaluation_tests'
export compilation_results='.vpl_compilation_results.txt'
export compilation_errors='.vpl_compilation_errors.txt'
export evaluation_results='.vpl_evaluation_results.txt'
export escaped_home_dir=$(echo $home_dir | sed "s/\//\\\\\//g")
export vpl_evaluation_test_help='.vpl_evaluation_test_help'
cat <<'FILE_END' >> "$home_dir/$vpl_evaluation_test_help"
-MANUAL: Testing Automatic Evaluation of a VPL Activity
This guide walks you through the steps to configure tests to check the automated evaluation in a VPL activity.
The aim of these checks is to test the automatic evaluation of different solutions and present the resultant outcomes.
For accurate evaluation:
 * A correct solution to the problem must be present.
 * Several incorrect solutions should also be available.
 * If the activity has variations, then distinct solutions for each variation are required.

-Tests configuration
For each solutions as a test case of the automatic evaluation, create a directory with the necessary files for that solution.
The directory name should reflect: the type of solution (pass or fail), the specific name of the solution, and the expected grade mark (optional).
The directory name should follow the format: [pass|fail]-solution name[-expected grade].
Examples:
>  pass-Correct solution-10
>  fail-Output error-7
>  fail-Infinite loop-0
>  fail-Compilation errors

-Directory Structure
Place all solution directories within the vpl_evaluation_tests directory.
If there are variations in the activity, then within vpl_evaluation_tests, create separate directories for each variation, named after the specific variation identification. Each of these variation directories should contain the solutions pertinent to that variation.

-Security Precautions
VPL will automatically delete the vpl_evaluation_tests directory in any scenario other than the evaluation check.
This measure ensures students cannot access the directory, safeguarding the integrity of the solutions.
FILE_END

function add_error {
	(
		[[ ! -s "$home_dir/$compilation_errors" ]] && echo "$fail_symbol Configuration errors found $bug_symbol"
		echo  "$1"
	)  >> "$home_dir/$compilation_errors"
}

function copy_configuration {
	cp -a $home_dir/* "$1"
	rm "$1/vpl_test_evaluate.sh"
	[[ -s "$1/.localenvironment.sh" ]] && cat "$1/.localenvironment.sh" >> "$1/vpl_environment.sh"
}

function compile_a_solution_test {
	local solution=$1
	copy_configuration "$solution"
	[[ $solution =~ $directory_format ]]
	local test_type="${BASH_REMATCH[1]}"
	local test_name="${BASH_REMATCH[2]}"
	local test_mark="${BASH_REMATCH[4]}"
	local test_info=''
	let ntest=$ntest+1
	local title="$ntest) Preparing '$test_name' solution ($test_type type)"
	if [[ $test_mark = "" ]] ; then
		if [[ $test_type = "pass" ]] ; then
			title="$title expected grade mark $VPL_GRADEMAX"
		else
			title="$title expected grade mark < $VPL_GRADEMAX"
		fi
	else
		title="$title expected grade mark $test_mark"
	fi
	cd "$solution"
	./vpl_evaluate.sh &> "$solution/$compilation_results"
	# Check compilation results
	if [ ! -x vpl_execution ] ; then
		title="$title (Fail)"
		test_info="$bug_symbol The creation of the evaluation program for a $test_type case has failed ($test_name).
$fail_symbol (Fail)"
	else
		title="$title (OK)"
		test_info=""
	fi
	echo "$title" >> "$home_dir/$compilation_results"
	[[ "$test_info" != "" ]] && echo "$test_info" >> "$home_dir/$compilation_results"
	cd "$home_dir"
}

function compile_solutions_tests {
	local solutions="$1/*"
	local solution=
	local correct=
	if [[ ! -d $1 ]] ; then
		add_error "$bug_symbol Directory of solutions ($1) not found."
		return
	fi
	# Find configuration errors
	for solution in $solutions ; do
		if [ ! -d "$solution" ] ; then
			add_error "$bug_symbol Found a file instead of a solution diretory ($solution)."
			rm "$solution"
			continue
		fi
		if [[ $solution =~ $directory_format ]] ; then
			[[ ${BASH_REMATCH[1]} = "pass" ]] && correct="$solution"
		else
			add_error "$bug_symbol Found a directory that does not match solution diretory format $directory_format ($solution)."
			rm -R "$solution"
		fi
	done
	if [[ $correct = "" ]] ; then
		add_error "$bug_symbol No correct solution is set for the $variation problem. Please, add a correct solution."
	fi
	for solution in $solutions ; do
		[[ -d "$solution" ]] && compile_a_solution_test "$solution"
	done
}

# Start running the compilation scripts.

let ntest=0
echo "━━━━ COMPILATION REPORT ━━━━"  >> "$home_dir/$compilation_results"
echo "$star_symbol Using '$VPL_PLN' programming language (or custom code) to run solutions" >> "$home_dir/$compilation_results"
if [[ -d "$home_dir/$oldtest_dir" ]] ; then
	mv "$home_dir/$oldtest_dir" "$home_dir/$test_dir"
	if [[ "$VPL_VARIATIONS" != "" ]] ; then
		for variation in ${VPL_VARIATIONS[@]} ; do
			echo "⤨ Preparing variation: $variation" >> "$home_dir/$compilation_results"
			compile_solutions_tests "$home_dir/$test_dir/$variation"
		done
	else
		compile_solutions_tests "$home_dir/$test_dir"
	fi
else
	add_error "$bug_symbol The required directory $oldtest_dir doesn't exist."
fi
cp common_script.sh vpl_execution

cat <<'SCRIPT_END' >>vpl_execution
if [ "$VPL_MAXTIME" = "" ] ; then
	export VPL_MAXTIME=20
fi
let VPL_MAXTIME=$VPL_MAXTIME+5;
if [ "$VPL_GRADEMAX" == "" ] ; then
	export VPL_GRADEMIN=0
	export VPL_GRADEMAX=10
	echo "Note: Using default grade marks 0..10"
fi
export pass_symbol='✅'
export fail_symbol='❌'
export bug_symbol='🐞'
export star_symbol='⭐'
export number_format=" *([0-9]+([.][0-9]*)?|[.][0-9]+) *"
export directory_format="/(pass|fail)- *([^-]*) *(-$number_format)?$"
export home_dir=$(pwd)
export test_dir='.vpl_evaluation_tests'
export compilation_results='.vpl_compilation_results.txt'
export evaluation_results='.vpl_evaluation_results.txt'
export compilation_errors='.vpl_compilation_errors.txt'
export vpl_evaluation_test_help='.vpl_evaluation_test_help'

function echo_VPL {
	echo '<|--'
	echo "$1"
	echo '--|>'
}
function echo_line_VPL {
	[[ $1 != "" ]] && echo "Comment :=>>$1"
}
function echo_VPL_file {
	echo_VPL "$(cat "$1")"
}
function run_a_solution_test {
	local solution=$1
	[[ $solution =~ $directory_format ]]
	local test_type="${BASH_REMATCH[1]}"
	local test_name="${BASH_REMATCH[2]}"
	local test_mark="${BASH_REMATCH[4]}"
	local test_info=''
	local mark=''
	cd "$1"
	let ntest=$ntest+1
	if [ -x vpl_execution ] ; then
		./vpl_execution  &> "$solution/$evaluation_results"
	else
		test_info="$bug_symbol Progran test for '$test_name' not generated. See compilation."
		echo "$test_info" > "$solution/$evaluation_results"
	fi
	# Check evaluation results
	OP='=='
	if [[ $test_mark == "" ]] ; then
		test_mark=$VPL_GRADEMAX
		if [[ $test_type == 'fail' ]] ; then
			OP='<'
		fi
	fi
	mark=$(grep -E 'Grade :=>>' "$solution/$evaluation_results" | tail -n1 | sed 's/Grade :=>> *//i')
	if [[ $mark =~ $number_format ]] ; then
		if [[ $(awk "END { print $mark $OP $test_mark }" <<< '') = 1 ]] ; then
			pass=$pass_symbol
		else
			pass=$fail_symbol
		fi
	else
		pass=$fail_symbol
	fi

	title="-$ntest) $pass "
	[[ $variation != "" ]] && title="$title⤨ $variation: "
	title="$title$test_name ($test_type type)"
	title="$title expected grade mark $OP $test_mark"
	echo_line_VPL "$title"
	if [[ $pass = $fail_symbol ]] ; then
		let ntestfail=$ntestfail+1
		[[ $test_info != "" ]] && echo_line_VPL "$test_info"
	fi
	test_info="Resulted grade mark ✓ '$mark' and expected grade mark $OP '$test_mark'"
	echo_line_VPL "$test_info"
	(
		echo_line_VPL "$title (FULL REPORT)"
		echo_line_VPL "$test_info"
		if [[ -s "$solution/$compilation_results" ]] ; then
			echo_line_VPL "  ━━━ Compilation output ━━━"
			echo_VPL_file "$solution/$compilation_results"
		fi
		echo_line_VPL "  ━━━ Evaluation output ━━━"
		cat "$solution/$evaluation_results"
	) >> "$home_dir/$evaluation_results"
	cd "$home_dir"
}

function run_solutions_tests {
	local solutions="$1/*"
	local solution=
	for solution in $solutions ; do
		run_a_solution_test "$solution"
	done
}

# If compilation erros stop
if [[ -s "$home_dir/$compilation_errors" ]] ; then
	[ -s "$home_dir/$compilation_results" ] && echo_VPL_file "$home_dir/$compilation_results"
	echo_VPL_file "$home_dir/$compilation_errors"
	echo_VPL_file "$home_dir/$vpl_evaluation_test_help"
	exit
fi

if [ -s "$home_dir/$compilation_results" ] ; then
	echo_VPL_file "$home_dir/$compilation_results"
fi

echo_line_VPL "━━━━ EVALUATION REPORT ━━━━"

let ntest=0
let ntestfail=0
if [[ "$VPL_VARIATIONS" != "" ]] ; then
	for variation in ${VPL_VARIATIONS[@]} ; do
		run_solutions_tests "$home_dir/$test_dir/$variation"
	done
else
	run_solutions_tests "$home_dir/$test_dir"
fi
echo_line_VPL
if [[ $ntestfail -gt 0 ]] ; then
	echo_line_VPL "- $star_symbol Final report: $fail_symbol $ntestfail of $ntest tests failed."
	global_mark=$VPL_GRADEMIN
else
	echo_line_VPL "- $star_symbol Final report: $pass_symbol All $ntest tests passed."
	global_mark=$VPL_GRADEMAX
fi
echo_line_VPL "━━━━ FULL EVALUATION REPORT ━━━━"
cat "$home_dir/$evaluation_results"
echo "Grade :=>>$global_mark"
if [[ $global_mark = $VPL_GRADEMAX ]] ; then
	exit 0
else
	exit 1
fi
SCRIPT_END

chmod +x vpl_execution

