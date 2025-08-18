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
-📘 Manual: Testing the Automatic Evaluation of a VPL Activity
This guide describes how to configure test cases to validate the automatic evaluation system in a Virtual Programming Lab (VPL) activity.
The purpose is to verify that the evaluation system correctly grades both correct and incorrect solutions, ensuring reliable automated assessment.

-✅ Requirements for Reliable Evaluation
To carry out a meaningful test of the evaluation logic:
 * ✅ Include at least one correct solution to the activity.
 * ❌ Provide several incorrect solutions, each demonstrating a specific type of failure (e.g., logic errors, runtime exceptions, infinite loops).
 * 🧩 (Optional) If the activity uses variations, include corresponding solutions for each variation.

🛠️

-🗂️ Test Case Configuration
Each test case must be placed in its own directory with a name that clearly indicates its purpose and expected result.

-🏷️ Directory Naming Format
Use the following format:
>  [pass|fail]-<solution_name>[-<expected_grade>]

* ✅ pass: A solution expected to be correct.
* ❌ fail: A solution expected to fail or receive a low grade.
* 📝 <solution_name>: A short, descriptive name of the scenario.
* 🎯 <expected_grade>: Optional. The expected grade of the solution.

-📂 Examples
>  pass-Correct solution-10
>  fail-Output mismatch-3
>  fail-Infinite loop-0
>  fail-Compilation error

Each directory must contain the source code files of the solution to evaluate.

-🧱 Directory Structure
Create a root folder named vpl_evaluation_tests.
Place all test case directories inside it.
Example structure:
>  vpl_evaluation_tests/
>  ├── pass-Correct solution-10/program.c
>  ├── fail-Output mismatch-3/program.c
>  ├── fail-Infinite loop-0/program.c
>  └── fail-Compilation error/program.c

If your activity includes variations, create a subdirectory within vpl_evaluation_tests for each variation. Name each subdirectory after the variation's identifier.
>  vpl_evaluation_tests/
>  └── variationA/
>  │   ├── pass-Correct for variation A-10/program.c
>  │   ├── fail-Output mismatch-3/program.c
>  │   ├── fail-Infinite loop-0/program.c
>  │   └── fail-Bad input handling-0/
>  ├── variationB/
>  │   ├── pass-Correct for variation B-10/program.c
>  │   ├── fail-Output mismatch-3/program.c
>  │   ├── fail-Infinite loop-0/program.c
>  │   └── fail-Bad input handling-0/program.c
>  ...

-🚀 Running the Evaluation
To run the test evaluation:
 * 📄 Navigate to the "Execution files" page of the VPL activity.
 * ▶️ Click the "Evaluate" button to start the process.
This step must be performed manually by the instructor.

-🔒 Security Measures
VPL automatically deletes the vpl_evaluation_tests folder outside of evaluation mode to prevent students from accessing test cases.
This ensures the integrity and confidentiality of your test solutions.

-🕒 When to Use This
Use this testing setup:
 * 🧪 During initial development of a VPL activity.
 * ⚙️ After modifying system settings, such as compiler or runtime updates.
 * 🩺 When debugging or verifying changes in grading logic.

FILE_END

function add_error {
	(
		[[ ! -s "$home_dir/$compilation_errors" ]] && echo "$fail_symbol Configuration errors found $bug_symbol"
		echo  "$1"
	)  >> "$home_dir/$compilation_errors"
}

function copy_configuration {
	# $1 The test case directory
	# Replicate evaluation environment in a test case
	# Copy all except the .vpl_evaluation_tests directory
	local savedir=$(pwd)
	local envfile="$1/vpl_environment.sh"
	cd "$homedir"
	(
		shopt -s dotglob nullglob
		for item in *; do
			if [ -f "$item" ]; then
				cp -a "$item" "$1/"
			elif [ "$item" != "$test_dir" ]; then
				cp -a "$item" "$1/"
			fi
		done
	)
	rm "$1/vpl_test_evaluate.sh"
	[[ -s "$1/.localenvironment.sh" ]] && cat "$1/.localenvironment.sh" >> "$envfile"
	[ ! -f "$envfile" ] && echo "#!/bin/bash" > "$envfile"
	[ -n "$variation" ] && echo "export VPL_VARIATION=\"$variation\"" >> "$envfile"
	chmod +x "$envfile"
	cd "$savedir"
}

function compile_a_solution_test {
	local solution="$1"
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
	./$VPL_EVALUATION_SCRIPT &> "$solution/$compilation_results"
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
if [[ -d "$home_dir/$oldtest_dir" ]] ; then
	echo "⭐⭐ COMPILATION REPORT ⭐⭐"  >> "$home_dir/$compilation_results"
	echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"  >> "$home_dir/$compilation_results"
	echo ""  >> "$home_dir/$compilation_results"
	echo "$star_symbol Using '$VPL_PLN' programming language (or custom code) to run solutions" >> "$home_dir/$compilation_results"
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
	add_error "$bug_symbol The required directory $oldtest_dir doesn't exist. Please, create it with the required test cases. Read the manual for more information."
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
	local solution="$1"
	[[ $solution =~ $directory_format ]]
	local test_type="${BASH_REMATCH[1]}"
	local test_name="${BASH_REMATCH[2]}"
	local test_mark="${BASH_REMATCH[4]}"
	local test_info=''
	local mark=''
	cd "$1"
	let ntest=$ntest+1
	if [ -x vpl_execution ] ; then
		[[ $variation != "" ]] && export VPL_VARIATION="$variation"
		[[ -f ./vpl_environment.sh ]] && echo "export VPL_VARIATION\"$variation\"" >> ./vpl_environment.sh
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
		[ -d "$solution" ] && run_a_solution_test "$solution"
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
echo_line_VPL " "
echo_line_VPL " "
echo_line_VPL "⭐⭐ EVALUATION REPORT ⭐⭐"
echo_line_VPL "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo_line_VPL " "

let ntest=0
let ntestfail=0
if [[ "$VPL_VARIATIONS" != "" ]] ; then
	for variation in ${VPL_VARIATIONS[@]} ; do
		run_solutions_tests "$home_dir/$test_dir/$variation"
	done
else
	run_solutions_tests "$home_dir/$test_dir"
fi
echo_line_VPL " "
if [[ $ntestfail -gt 0 ]] ; then
	echo_line_VPL "- $star_symbol Final report: $fail_symbol $ntestfail of $ntest tests failed."
	global_mark=$VPL_GRADEMIN
else
	echo_line_VPL "- $star_symbol Final report: $pass_symbol All $ntest tests passed."
	global_mark=$VPL_GRADEMAX
fi
echo_line_VPL " "
echo_line_VPL " "
echo_line_VPL "⭐⭐ FULL EVALUATION REPORT ⭐⭐"
echo_line_VPL "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo_line_VPL " "
cat "$home_dir/$evaluation_results"
echo "Grade :=>>$global_mark"
if [[ $global_mark = $VPL_GRADEMAX ]] ; then
	exit 0
else
	exit 1
fi
SCRIPT_END

chmod +x vpl_execution
