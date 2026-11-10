#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

# Run all suites with: bash tests/default_scripts_test.sh
# Run one suite with: bash tests/default_scripts_test.sh startup
set -e

TESTS_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/default_scripts" && pwd)
source "$TESTS_DIR/helpers.sh"

WORKDIR=$(mktemp -d)
trap 'rmdir -- "$WORKDIR"' EXIT

CASE_COUNT=0
FAILED_CASES=0
SKIPPED_CASES=0
if [ "$#" -eq 0 ]; then
    test_files=("$TESTS_DIR"/*_test.sh)
else
    test_files=()
    for suite in "$@"; do
        if [[ ! "$suite" =~ ^[a-z][a-z0-9_]*$ ]] || [ ! -f "$TESTS_DIR/${suite}_test.sh" ]; then
            printf 'Unknown test suite: %s\n' "$suite" >&2
            exit 1
        fi
        test_files+=("$TESTS_DIR/${suite}_test.sh")
    done
fi
for test_file in "${test_files[@]}"; do
    source "$test_file"
done

printf '%s cases: %s passed, %s failed, %s skipped\n' "$CASE_COUNT" \
    "$((CASE_COUNT - FAILED_CASES - SKIPPED_CASES))" "$FAILED_CASES" "$SKIPPED_CASES"
if [ "$FAILED_CASES" -ne 0 ]; then
    exit 1
fi
