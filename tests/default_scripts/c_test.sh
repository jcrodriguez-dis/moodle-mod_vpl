#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_response_file_quotes {
    if ! command -v gcc > /dev/null; then
        printf 'SKIP: gcc is not installed\n'
        return 77
    fi
    local filename=${1:-say\"hi\".c}
    CASE_FILES+=("$filename")
    if ! touch "$filename"; then
        printf 'SKIP: filesystem cannot create <%s>\n' "$filename"
        return 77
    fi
    printf 'int main(void) { return 0; }\n' > "$filename"
    prepare_submission "$filename"
    bash "$SCRIPTS_DIR/c_run.sh" batch
    assert_file vpl_execution
    ./vpl_execution
}

run_case 'GCC response files preserve literal quotes' test_response_file_quotes
run_case 'GCC response files preserve backslashes' test_response_file_quotes 'path\name.c'
