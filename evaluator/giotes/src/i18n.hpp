/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
#pragma once
#include <map>
#include "tools.hpp"

/**
 * @brief I18nCode enum for the i18n str names
 * 
 */
enum I18nCode {
    str_error_parameter_unknow,
    str_error_text_out,
    str_no_test_cases,
    str_global_timeout,
    str_stop_requested,
    str_fatal_errors,
    str_output_too_large,
    str_command_line_too_long,
    str_too_many_command_arguments,
    str_execution_file_not_found,
    str_forkpty_error,
    str_program_terminated_by_signal,
    str_child_terminated_by_signal,
    str_child_continued,
    str_program_terminated_by_unknown_reason,
    str_waitpid_error,
    str_term_signal,
    str_internal_error,
};

/**
 * @brief struct for string code and the defualt string for using if envorment var fails.
 * 
 */
struct StrCodeStrDefault {
    string code;
    string str;
    StrCodeStrDefault(string code, string str) : code(code), str(str) {}
};

/**
 * @brief I18nCodeStrDefault map for mapping from I18nCode to StrCodeStrDefault
 * 
 */
const std::map<I18nCode, StrCodeStrDefault> I18nCodeStrDefault = {
    {str_error_parameter_unknow, StrCodeStrDefault("error_parameter_unknow", "Syntax error in cases file (line:{$a}) unknow parameter")},
    {str_error_text_out, StrCodeStrDefault("error_text_out", "Syntax error in cases file (line:{$a}) text out of parameter or comment")},
    {str_no_test_cases, StrCodeStrDefault("no_test_cases", "No test cases found in the cases file")},
    {str_global_timeout, StrCodeStrDefault("global_timeout", "Global timeout")},
    {str_stop_requested, StrCodeStrDefault("stop_requested", "Stop requested by the system")},
    {str_fatal_errors, StrCodeStrDefault("fatal_errors", "Fatal errors")},
    {str_output_too_large, StrCodeStrDefault("output_too_large", "Program output too large ({$a}Kb)")},
    {str_command_line_too_long, StrCodeStrDefault("command_line_too_long", "Command line too long: cutted command line")},
    {str_too_many_command_arguments, StrCodeStrDefault("too__many_command_arguments", "Too many command arguments: cutted parameters")},
    {str_execution_file_not_found, StrCodeStrDefault("execution_file_not_found", "Execution file not found: '{$a}'")},
    {str_forkpty_error, StrCodeStrDefault("forkpty_error", "Internal error: forkpty error ({$a})")},
    {str_program_terminated_by_signal, StrCodeStrDefault("program_terminated_by_signal", "Program terminated by signal: {$a->signal} ({$a->signum})")},
    {str_child_terminated_by_signal, StrCodeStrDefault("child_terminated_by_signal", "Child terminated by signal: {$a->signal} ({$a->signum})")},
    {str_child_continued, StrCodeStrDefault("child_continued", "Child process was continued")},
    {str_program_terminated_by_unknown_reason, StrCodeStrDefault("program_terminated_by_unknown_reason", "Program terminated by unknown reason: {$a}")},
    {str_waitpid_error, StrCodeStrDefault("waitpid_error", "Internal error: waitpid error ({$a})")},
    {str_term_signal, StrCodeStrDefault("term_signal", "Global test timeout (TERM signal received)")},
    {str_internal_error, StrCodeStrDefault("internal_error", "Internal test error")},
};

/**
 * @brief getString function to get the string from the I18nCode
 * 
 * Get the string from the environment parameter as VPLEVALUATOR_STR_<code>
 * or use default string.
 * 
 * @param code I18nCode
 * @return string
 */
string getString(I18nCode code) {
    auto it = I18nCodeStrDefault.find(code);
    if (it == I18nCodeStrDefault.end()) {
        return "Unknown code";            
    } else {
        auto s2 = it->second;
        return Tools::getenv("VPLEVALUATOR_STR_" + s2.code, s2.str, false);
    }
}

/**
 * @brief getString function to get the string from the I18nCode with a parameter.
 * 
 * 
 * Get the string from getString and then replace parameter.
 * 
 * @param code I18nCode 
 * @param param string to replace the placeholder {$a}
 * @return string
 */
string getString(I18nCode code, string param) {
    string str = getString(code);
    Tools::replaceAll(str, "{$a}", param);
    return str;
}

/**
 * @brief getString function to get the string from the I18nCode with a parameter.
 * 
 * 
 * Get the string from getString and then replace parameter.
 * 
 * @param code I18nCode 
 * @param param int to replace the placeholder {$a}
 * @return string
 */
string getString(I18nCode code, long int param) {
    string str = getString(code);
    Tools::replaceAll(str, "{$a}", Tools::int2str(param));
    return str;
}


/**
 * @brief getString function to get the string from the I18nCode with two parameters.
 * 
 * Get the string from getString and then replace parameters.
 * 
 * @param code I18nCode 
 * @param name1 string to generate the placeholder {$a->name1}
 * @param param1 string to replace the placeholder {$a->param1}
 * @param name2 string to generate the placeholder {$a->name2}
 * @param param2 string to replace the placeholder {$a->param2}
 * @return string
 */
string getString(I18nCode code, string name1, string param1, string name2, string param2) {
    string str = getString(code);
    Tools::replaceAll(str, "{$a->" + name1 + "}", param1);
    Tools::replaceAll(str, "{$a->" + name2 + "}", param2);
    return str;
}
