#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_python_filename {
    export EXPECTED_FILENAME=$1
    CASE_FILES+=("$EXPECTED_FILENAME")
    if ! touch "$EXPECTED_FILENAME"; then
        printf 'SKIP: filesystem cannot create <%s>\n' "$EXPECTED_FILENAME"
        return 77
    fi
    prepare_submission "$EXPECTED_FILENAME"
    function python3 {
        check_filename "$@"
    }
    export -f python3
    export PRICE=expanded
    bash "$SCRIPTS_DIR/python-3_run.sh" batch
    assert_file vpl_execution
    bash vpl_execution
}

run_case 'Python preserves dollar signs' test_python_filename 'cash$PRICE.py'
run_case 'Python preserves literal quotes' test_python_filename 'say"hi".py'
