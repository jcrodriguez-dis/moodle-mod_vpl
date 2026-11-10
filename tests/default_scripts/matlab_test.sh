#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_matlab_spaces {
    local filename='two words.m'
    CASE_FILES+=("$filename" startup.m)
    printf 'disp("hello");\n' > "$filename"
    prepare_submission "$filename"
    function matlab {
        return 0
    }
    export -f matlab
    bash "$SCRIPTS_DIR/matlab_run.sh" batch
    assert_file startup.m
    cmp "$filename" startup.m
}

run_case 'MATLAB copies filenames with spaces' test_matlab_spaces
