# BIOTES Manual

**BIOTES** (Basic Input/Output Test Evaluation System) is a feature provided by **VPL**, available both out of the box and as an evaluator subplugin.
This system allows teachers to automatically evaluate students’ programs by defining test cases that specify the program’s input and the expected output.

## Introduction
This document shows the test case description language used by BIOTES.
The teachers, using this language, write the test cases in the `vpl_evaluate.cases` file (action menu "Test cases") for evaluating the students' programs.

The language uses statements with the format `"statement_name = value"`.
Based on the statement type, the value can take only one line or span multiple ones.
A multiline value ends when another statement appears.
Notice that this behavior limits the valid contents of the values of the statement.
The statement name is case-insensitive.
A basic test case definition includes a case name, the input we want to provide to the student's program, and the output we expect from it.
We can also configure other stuff, such as the penalization for failed tests, timeout, etc.
VPL will run the evaluation using the test cases in `vpl_evaluate.cases` and generate a report of failed cases and the mark obtained.

---

## Basic testing

### Case

This statement starts a new case definition and states the case description.

```
Format: "Case = Test case description"
```

The case description occupies only one line.
This description will appear in the report if the case fails.

**Example:**

![Minimum test cases definition](@@@IMAGESPATH@@@/basic_case_definition.png)

![Evaluation report showing the failed test case](@@@IMAGESPATH@@@/basic_case_fail.png)

![Evaluation report showing the passed test case](@@@IMAGESPATH@@@/basic_case_pass.png)

---

### Input

This statement defines the text to send to the student program as input.
Each case requires one and only one input statement.
Its value **can span multiple lines**.
The system does not control if the student program reads or not the input.

```
Format: "Input = text"
```

**Example 1:**

![Example of input of a line of numbers](@@@IMAGESPATH@@@/basic_input_definition1.png)

**Example 2:**

![Example of input of multiple lines of numbers](@@@IMAGESPATH@@@/basic_input_definition2.png)

**Example 3:**

![Example of input of a line of text](@@@IMAGESPATH@@@/basic_input_definition3.png)

**Example 4:**

![Example of input of multiple lines of text](@@@IMAGESPATH@@@/basic_input_definition4.png)

---

### Output

The output statement defines a possible correct output of the student program for the input of the current case.
A test case can have multiple output statements and at least one.
If the program output matches one of the output statements, the test case succeeds; otherwise, it fails.
There are four kinds of values for an output statement: numbers, text, exact text, and regular expression.

```
Format: "Output = value"
```

The value of the output **can span multiple lines**.

---

#### Checking only numbers

To define this type of output check, you must use only numbers as values in the output statement.
The values can be integers or floating numbers.
This type of output checks numbers in the output of the student's program, ignoring the rest of the text.
The output of the student's program is filtered, removing the non-numeric text.
Finally, the system compares the resulting numbers of the output with the expected ones, using a tolerance when comparing floating numbers.

**Example 1:**

![Example of output of type number](@@@IMAGESPATH@@@/basic_input_definition1.png)

![Example of incorrect program for output of type number](@@@IMAGESPATH@@@/basic_input_definition1_fail.png)

![Example of correct program for output of type number](@@@IMAGESPATH@@@/basic_input_definition1_pass.png)

**Example 2:**

![Example of output of float numbers](@@@IMAGESPATH@@@/basic_output_numbers2.png)

![Incorrect program for float numbers](@@@IMAGESPATH@@@/basic_output_numbers2_fail.png)

![Correct program for float numbers](@@@IMAGESPATH@@@/basic_output_numbers2_pass.png)

---

#### Checking text

This type of output **is a nonstrict text check** comparing only words in the output of the student's program.
The comparison is **case-insensitive and ignores punctuation marks, spaces, tabs, and newlines**.
To define this type of output check, you must use text that is not only numbers and not starting with a slash nor enclosed in double quotes.
A filter removes punctuation marks, spaces, tabs, and newlines from the program output, leaving a separator between each word.
Numbers are not punctuation marks and are not removed.
Finally, the system performs a case-insensitive comparison between the resulting text and the expected output.

**Example:**

![Example of output of type text](@@@IMAGESPATH@@@/basic_output_text1.png)

![Student's program that matches the defined output](@@@IMAGESPATH@@@/basic_output_text1_program.png)

![Student's program that passes the test](@@@IMAGESPATH@@@/basic_output_text1_pass.png)

---

#### Checking exact text

This type of output checks the **exact text** from the student's program output.
To define this type of output check, the teacher must use text enclosed in double quotes.
The system compares the output of the program with the defined output (after removing the quotes).

**Example 1:**

![Example of output of type exact text](@@@IMAGESPATH@@@/basic_output_exactext1.png)

![Program output that matches](@@@IMAGESPATH@@@/basic_output_exactext1_pass.png)

**Example 2:**

![Example of output of type exact text](@@@IMAGESPATH@@@/basic_output_exactext2.png)

![Program output that matches](@@@IMAGESPATH@@@/basic_output_exactext2_pass.png)

---

### Checking regular expression

The evaluator can define this type of check by starting the output value with a slash `/` and ending with another slash `/`, optionally followed by one or several modifiers.
This format is similar to JavaScript literal regular expressions, but uses POSIX regex instead.

**Example:**

![Example of output of type regular expression](@@@IMAGESPATH@@@/basic_output_regex1.png)

![Program output that matches (example 1)](@@@IMAGESPATH@@@/basic_output_regex1_pass1.png)

![Program output that matches (example 2)](@@@IMAGESPATH@@@/basic_output_regex1_pass2.png)

*Student's program output that matches the output definition.*

---

### Multiple output checking

The test case definition may contain multiple output statements, meaning that if any of them matches, the case succeeds.

**Example 1:**

![Example of multiple outputs of different types](@@@IMAGESPATH@@@/basic_multioutput1.png)

![Program output that matches (example 1)](@@@IMAGESPATH@@@/basic_multioutput1_pass1.png)

![Program output that matches (example 2)](@@@IMAGESPATH@@@/basic_multioutput1_pass2.png)

![Program output that matches (example 3)](@@@IMAGESPATH@@@/basic_multioutput1_pass3.png)

*Student's program output that matches the output definition.*

![Program output that does not match (example 1)](@@@IMAGESPATH@@@/basic_multioutput1_fail1.png)

![Program output that does not match (example 2)](@@@IMAGESPATH@@@/basic_multioutput1_fail2.png)

*Student's program output that does not match the output definition.*

**Example 2:**

![Example of multiple outputs of numbers type](@@@IMAGESPATH@@@/basic_multioutput2.png)

---

### Penalizations and final grade

A test case fails if its output does not match an expected value.
By default, the penalty applied when a test case fails is `grade_range / number_of_cases`.
The penalties of all failed test cases are summed to obtain the overall penalization.
The final grade is the maximum mark minus the total penalization.
The final grade value is never less than the minimum grade or greater than the maximum grade of the VPL activity.

---

## Advanced testing

### Custom penalization

The evaluator can customize the penalty of a test case using the following statement:

```
Format: "Grade reduction = [ value | percent% ]"
```

The penalty can be a percentage or a specific value.
The final grade will be the maximum grade for the activity minus the overall penalization.
If the result is less than the minimum grade, the minimum is applied.

**Example:**

![Example of fail penalization customized](@@@IMAGESPATH@@@/advanced_grade_reduction1.png)

![Student's program output that fails the evaluation](@@@IMAGESPATH@@@/advanced_grade_reduction1_fail.png)

---

### Controlling the messages in the output report

When a test fails, BIOTES adds to the report the details of the input, expected output, and output found.
When a test case fails and has a `"Fail message"` statement, the system instead shows that message instead of the default input/output report.

The `"Fail message"` statement allows the evaluator to hide the data used in the case.
A student knowing the inputs and outputs might code a solution that passes the tests without actually solving the problem.
If the `"Fail message"` statement appears in a test case and the case fails, the report will contain only that message.

```
Format: "Fail message = message"
```

This statement must go **before** the input.

**Example:**

![Example of fail message customization](@@@IMAGESPATH@@@/advanced_fail_message1.png)

![Student's program output that fails](@@@IMAGESPATH@@@/advanced_fail_message1_fail.png)

---

### Running another program

The teacher can use another program to test a different feature of the student’s program.
This allows running static/dynamic analysis of the student code (e.g., using *checkstyle* [#checkstyle] to check Java style).
The `"Program to run"` replaces the student’s program for another one in that test case.

```
Format: "Program to run = path"
```

**Example:**

![Example of using "Program to run" statement](@@@IMAGESPATH@@@/advanced_program_to_run1.png)

> **Note:** If you plan to use a custom script as in the example, remember to mark it in the *files to keep when running*.

---

### Program arguments

This statement allows sending information as command-line arguments to the student program or the `"program to run"` if set.
This statement is compatible with the input statement.

```
Format: "Program arguments = arg1 arg2 …"
```

**Example 1:**

This example shows how to use the `"Program to run"` and `"Program arguments"` statements to check if the student's program creates a file with a name passed as a command-line argument.

![Example using "Program arguments" statement](@@@IMAGESPATH@@@/advanced_program_arguments1.png)

![Code of the check\_file\_exist.sh script](@@@IMAGESPATH@@@/check_file_exist_code.png)

**Example 2:**

This example shows how a teacher can use the `"Program to run"` and `"Program arguments"` statements to evaluate a SQL query exercise using different data sets.

![Example of using "Program to run" and "Program arguments"](@@@IMAGESPATH@@@/advanced_program_arguments2.png)

> **Note:** If you plan to use a custom script as in the example, remember to mark it in the *files to keep when running*.

---

### Expected exit code

This statement sets the expected exit code of the program case execution.
The test case succeeds if the exit code matches.
The test case also succeeds if an output matches.

```
Format: "Expected exit code = number"
```

The next example shows how `"Program to run"` and `"Program arguments"` statements can execute different programs.
The first case renames a file, the second compiles it, and the third runs the resulting program.

![Example using exit code statements](@@@IMAGESPATH@@@/advanced_exit_code1.png)

---

### Variation

This statement specifies that the test case must only apply if the indicated variation was assigned to the current student.
If the variation does not match, the test case is ignored.

```
Format: "Variation = identification"
```

**Example:**

![Example of using the "Variation" statement](@@@IMAGESPATH@@@/advanced_variation1.png)

---

### References

[#checkstyle] [http://checkstyle.sourceforge.net/](http://checkstyle.sourceforge.net/)

For more details about VPL, visit the [VPL home page](https://vpl.dis.ulpgc.es/) or the [VPL plugin page at Moodle](https://moodle.org/plugins/mod_vpl).

## License & authorship

© Copyright 2021, Juan Carlos Rodríguez-del-Pino [jc.rodriguezdelpino@ulpgc.es](mailto:jc.rodriguezdelpino@ulpgc.es).

This documentation is licensed under a 
[Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License](https://creativecommons.org/licenses/by-nc-nd/4.0/).

[![CC BY-NC-ND 4.0 License](https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png)](https://creativecommons.org/licenses/by-nc-nd/4.0/)