/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#pragma once

#include <string>
using namespace std;
/**
 * Constant strings classes
 * 
 */
class MessageMarks {
public:
    static const string case_id;
    static const string case_title;
    static const string input;
    static const string input_inline;
    static const string check_type;
    static const string test_result_mark;
    static const string fail_mark;
    static const string pass_mark;
    static const string error_mark;
    static const string timeout_mark;
    static const string expected_output;
    static const string expected_output_inline;
    static const string program_output;
    static const string program_output_inline;
    static const string expected_exit_code;
    static const string exit_code;
    static const string time_limit;
    static const string num_tests;
    static const string num_tests_run;
    static const string num_tests_failed;
    static const string num_tests_passed;
    static const string num_tests_timeout;
    static const string num_tests_error;
    static const string grade_reduction;
};

class DefaultMessage {
public:
    static const string fail_output;
    static const string timeout;
    static const string fail_exit_code;
    static const string final_report;
    static const string title_format;
    static const string fail_mark;
    static const string pass_mark;
    static const string error_mark;
    static const string timeout_mark;
};
