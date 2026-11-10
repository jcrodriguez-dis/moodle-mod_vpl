#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_julia_spaces {
    export EXPECTED_FILENAME='two words.jl'
    CASE_FILES+=("$EXPECTED_FILENAME")
    touch "$EXPECTED_FILENAME"
    prepare_submission "$EXPECTED_FILENAME"
    function julia {
        check_filename "$@"
    }
    export -f julia
    bash "$SCRIPTS_DIR/julia_run.sh" batch
    assert_file vpl_execution
    bash vpl_execution
}

run_case 'Julia preserves spaces' test_julia_spaces
