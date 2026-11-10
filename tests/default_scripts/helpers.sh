#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

SCRIPTS_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../../jail/default_scripts" && pwd)

function assert_equal {
    if [ "$1" != "$2" ]; then
        printf 'FAIL: expected <%s>, got <%s>\n' "$1" "$2" >&2
        exit 1
    fi
}

function assert_file {
    if [ ! -f "$1" ]; then
        printf 'FAIL: expected file <%s>\n' "$1" >&2
        exit 1
    fi
}

function check_filename {
    assert_equal 1 "$#"
    assert_equal "$EXPECTED_FILENAME" "$1"
}
export -f assert_equal check_filename

function prepare_submission {
    local index=0 filename filenames=
    {
        for filename in "$@"; do
            printf 'export VPL_SUBFILE%s=%q\n' "$index" "$filename"
            filenames+="$filename"$'\n'
            index=$((index + 1))
        done
        printf 'export VPL_SUBFILES=%q\n' "$filenames"
        printf 'VPL_SUBFILES_ARRAY=('
        if [ "$#" -gt 0 ]; then
            printf ' %q' "$@"
        fi
        printf ' )\n'
    } > vpl_environment.sh
}

function cleanup_case {
    cd "$WORKDIR"
    if [ "$CASE_DIR" != "$WORKDIR/case-$CASE_COUNT" ]; then
        printf 'FAIL: refusing to clean unexpected case directory <%s>\n' "$CASE_DIR" >&2
        return 1
    fi
    rm -rf -- "$CASE_DIR"
}

function run_case {
    local name=$1
    shift
    CASE_COUNT=$((CASE_COUNT + 1))
    set +e
    (
        set -e
        CASE_DIR="$WORKDIR/case-$CASE_COUNT"
        mkdir "$CASE_DIR"
        cd "$CASE_DIR"
        CASE_FILES=(common_script.sh vpl_environment.sh vpl_execution vpl_wexecution
            .vpl_source_files .vpl_execution.error)
        CASE_DIRECTORIES=()
        trap cleanup_case EXIT
        cp "$SCRIPTS_DIR/common_script.sh" common_script.sh
        export PROFILE_RAN=yes CLASSPATH= HOME="$CASE_DIR" TMPDIR="$CASE_DIR"
        unset VPL_SUBFILE0 VPL_SUBFILE1 VPL_SUBFILES VPL_SUBFILES_ARRAY VPL_RUN_MODE
        unset VPL_EVALUATION_MODE VPL_HELLO_MODE VPL_DEBUG BASH_ENV ENV
        "$@"
    )
    local result=$?
    set -e
    case "$result" in
        0) printf 'PASS: %s\n' "$name" ;;
        77)
            SKIPPED_CASES=$((SKIPPED_CASES + 1))
            printf 'SKIP: %s\n' "$name"
            ;;
        *)
            FAILED_CASES=$((FAILED_CASES + 1))
            printf 'FAIL: %s (exit %s)\n' "$name" "$result" >&2
            ;;
    esac
}
