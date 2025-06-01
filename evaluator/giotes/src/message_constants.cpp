/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include "message_constants.hpp"

const string MessageMarks::case_id = "<<<case_id>>>";
const string MessageMarks::case_title = "<<<case_title>>>";
const string MessageMarks::test_result_mark = "<<<test_result_mark>>>";
const string MessageMarks::fail_mark = "<<<fail_mark>>>";
const string MessageMarks::pass_mark = "<<<pass_mark>>>";
const string MessageMarks::error_mark = "<<<error_mark>>>";
const string MessageMarks::timeout_mark = "<<<timeout_mark>>>";
const string MessageMarks::input = "<<<input>>>";
const string MessageMarks::input_inline = "<<<input_inline>>>";
const string MessageMarks::check_type = "<<<check_type>>>";
const string MessageMarks::expected_output = "<<<expected_output>>>";
const string MessageMarks::expected_output_inline = "<<<expected_output_inline>>>";
const string MessageMarks::program_output = "<<<program_output>>>";
const string MessageMarks::program_output_inline = "<<<program_output_inline>>>";
const string MessageMarks::expected_exit_code = "<<<expected_exit_code>>>";
const string MessageMarks::exit_code = "<<<exit_code>>>";
const string MessageMarks::time_limit = "<<<time_limit>>>";
const string MessageMarks::num_tests = "<<<num_tests>>>";
const string MessageMarks::num_tests_run = "<<<num_tests_run>>>";
const string MessageMarks::num_tests_failed = "<<<num_tests_failed>>>";
const string MessageMarks::num_tests_passed = "<<<num_tests_passed>>>";
const string MessageMarks::num_tests_timeout = "<<<num_tests_timeout>>>";
const string MessageMarks::num_tests_error = "<<<num_tests_error>>>";
const string MessageMarks::grade_reduction = "<<<grade_reduction>>>";


const string DefaultMessage::fail_output = "Incorrect program output\n"
			" --- Input ---\n" + MessageMarks::input + "\n"
			" --- Program output ---\n" + MessageMarks::program_output + "\n"
			" --- Expected output (" + MessageMarks::check_type + ")---\n" + MessageMarks::expected_output ;

const string DefaultMessage::timeout = "Program timeout after " + MessageMarks::time_limit + " sec\n"
									" --- Input ---\n" + MessageMarks::input + "\n"
									" --- Program output ---\n" + MessageMarks::program_output + "\n";

const string DefaultMessage::fail_exit_code = "Incorrect exit code. Expected " + MessageMarks::expected_exit_code +
											+ ", found " + MessageMarks::exit_code + "\n"
											" --- Input ---\n" + MessageMarks::input + "\n"
											" --- Program output ---\n" + MessageMarks::program_output + "\n";

const string DefaultMessage::final_report = "-Summary of tests\n"
											">+----------------------------------+\n"
											">| " + MessageMarks::num_tests_run + " test(s) run/" +
											MessageMarks::num_tests_passed + " test(s) passed |\n"
											">+----------------------------------+\n";

const string DefaultMessage::title_format = "Test " + MessageMarks::case_id + ": " +
											 MessageMarks::case_title;
const string DefaultMessage::fail_mark = "❌ fail";
const string DefaultMessage::pass_mark = "✅ pass";
const string DefaultMessage::error_mark = "💥 error";
const string DefaultMessage::timeout_mark = "⏱️ timeout";
