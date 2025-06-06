# DRAFT DOCUMENT
# GIOTES — General Input/Output Test Evaluation System

*(replacement for BIOTES in VPL)*

---

## 1  What is GIOTES?

GIOTES is general framework for the evaluation of programming submissions written in almost any language.
This framework is built to operate in VPL for Moodle ([VPL][1]) and generate reports and grade marks for that enviroment.
The goals of this frameworks are:
* Integration with VPL. This framework is an evaluator subplugin of VPL. This allow to be used in the known VPL for Moodle.
* Easy to use. Allow to write tests cases in an easy format.
* Report oriented. Allow to report the evaluation in a full customizable format.
* Compatiblity with BIOTES teh previous default VPL framework. 

GIOTES keeps the plain-text “statement = value” language that teachers already know from **BIOTES** and runs the same *`vpl_evaluate.cases`* files.
It adds:

* Customisable marks for pass, fail, timeout and error cases
* Customizable messages for pass, fail output, fail exit code, timeout case.
* A larger set of placeholders you can embed in your own messages
* A per-case **Case title format** you can redefine
* **multiline end** that let you stop a multiline value at any token you decide

---

## 2  Quick start

```text
# vpl_evaluate.cases (first steps). This is a comment

Case = Sum of two integers
Input =
3 4
Output = 7
Output = "The result is 7"
```

Upload this file as **Test cases** in the VPL activity.
GIOTES will execute the learner’s program, compare the output with the two possibilities and grade automatically.

---

## 3  The language

### 3.0  General struture of tests definitions (vpl_evaluate.cases)

* General options and default values for all cases.
  All settings before the first case definition.
* Sequense of cases definitions starting with "case =".
  All settings in each case replace default values,
  but "output=" that generate new output posibilities for pass.

  ├─── 📦 General Options and Defaults  (global scope)
  │    • Set before first 'case =' block.
  │    • Define default values for all cases.
  │    • Common examples:
  │        ├─ Grade reduction = 1
  │        ├─ Time limit = 3
  │        ├─ Fail message = 
  │        └─ ... (other global settings)
  │
  └─── # Cases Sequence  (one or more "case = ..." blocks)
  ├─── 📝 Case 1: case = test case 1
  │     ├─ input = 6 3
  │     ├─ output = 2
  │     └─ ... (other case-specific settings)
  │
  ├─── 📝 Case 2: case = test case 2
  │     ├─ input = 16 4
  │     ├─ output = 4
  │     └─ ... (other case-specific settings)
  │
  ├─── 📝 Case 3: case = test case 3
  │     ├─ input = 1 0
  │     ├─ output = Zero division
  │     └─ ... (other case-specific settings)
  │
  └─── 📝 Case N: case = test case N
        ├─ input = -4 2
        ├─ output = Negative number
        └─ ...

Notes:
 * Each "case =" block overrides global defaults.
 * "output =" adds *new* passing criteria (not replaces).
 * Cases are evaluated sequentially.

### 3.1  Required inside every case

* **Case =** one-line description
* **Input =** text sent to stdin (can span lines)
* **Output =** at least one expected result.
  *If the value is …*

  * only numbers → numeric check
  * plain text → word-by-word check
  * text in double quotes → exact text
  * `/regex/` → POSIX regular-expression check
    (All four behaviours existed in BIOTES .)

### 3.2  Optional inside a case

* **Grade reduction =** *value* | *percent%* — overrides the automatic penalty
* **Time limit =** seconds — per-case CPU limit
* **Expected exit code =** number — accept correct exit code even if the output mismatches
* **Program to run =** path — replace student executable for this case
* **Program args =** arg1 arg2 …
* **Fail message =** custom text shown when the case fails
* **Pass message**, **Timeout message**, **Fail exit code message** — similar idea
* **Variation =** id — case only runs if `$VPL_VARIATION` matches
* **multiline end =** token. The next multiline option will expand until the token — stop reading the following multiline value when the token is found
* **Case title format =** template with placeholders

### 3.3  Global-only statements

* **Fail mark / Pass mark / Error mark / Timeout mark** — symbol or text inserted through the `<<<test_result_mark>>>` placeholder
* **Final report message** — template appended after all cases

When the same statement appears more than once, the *last* one wins.

---

## 4  Placeholders you can use in any custom message or case title format

Below is the full list defined in *`message_constants.cpp`* :

```
<<<case_id>>>               <<<exit_code>>>
<<<case_title>>>            <<<time_limit>>>
<<<test_result_mark>>>      <<<num_tests>>>
<<<fail_mark>>>             <<<num_tests_run>>>
<<<pass_mark>>>             <<<num_tests_passed>>>
<<<error_mark>>>            <<<num_tests_failed>>>
<<<timeout_mark>>>          <<<num_tests_timeout>>>
<<<input>>>                 <<<num_tests_error>>>
<<<input_inline>>>          <<<grade_reduction>>>
<<<check_type>>>
<<<expected_output>>>       <<<program_output>>>
<<<expected_output_inline>>> <<<program_output_inline>>>
<<<expected_exit_code>>>
```

Use any of them inside **Fail message**, **Pass message**, **Final report message**, **Case title format**, etc.

---

## 5  Default messages (edit or replace)

GIOTES ships readable defaults.
Example — *fail due to wrong output* (stored as `DefaultMessage::fail_output`) :

```text
Incorrect program output
 --- Input ---
<<<input>>>
 --- Program output ---
<<<program_output>>>
 --- Expected output (<<<check_type>>>)---
<<<expected_output>>>
```

Provide your own text in the corresponding statement to override it.

---

## 6  How the grade is calculated

1. `grade_range = VPL_GRADEMAX − VPL_GRADEMIN` (defaults 0 to 10).
2. For each failed case GIOTES subtracts `grade_range / number_of_cases`.
3. **Grade reduction** inside a case replaces that automatic penalty.
4. The final grade is clamped to the activity range .

---

## 7  Environment variables recognised

* `VPL_GRADEMIN` (default 0)
* `VPL_GRADEMAX` (default 10)
* `VPL_MAXTIME` — total seconds for *all* cases (default 20)
* `VPL_VARIATION` — current variation id (empty by default)

---

## 8  Examples you can reuse

### 8.1 Custom marks and case titles

```text
Fail mark = 🔴
Pass mark = 🟢
Case title format = 🧪 <<<case_title>>> — <<<test_result_mark>>>

Case = Compile
Input =
Output = ""
```

### 8.2 Hidden input / output

```text
Fail message =
Wrong answer. Try again!

Case = Secret test
Fail message = See above
Input =
10
20
Output = 30
```

---

## 9  Migrating an old BIOTES file


---

## 10  Troubleshooting cheatsheet

* *“Fatal errors: tests not run”* → typo in a statement name or missing *Input/Output*
* All cases ignored → **Variation** does not match `$VPL_VARIATION`.
* Always grade 0 → `VPL_GRADEMAX` ≤ `VPL_GRADEMIN`.

---

## 11  Licence & authorship

GIOTES is part of **Virtual Programming Lab** and distributed under the **GNU GPL v3 or later**.
Code and documentation © 2025 Juan Carlos Rodríguez-del-Pino.

---

*Happy automated grading!* 🎉

[1]: https://vpl.dis.ulpgc.es/documentation/vpl-3.4.3%2B/biotes.html "6. Automated program assessment — Virtual Programming Lab for Moodle (VPL) 3.4.3+ documentation"
