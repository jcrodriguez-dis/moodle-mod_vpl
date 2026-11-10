#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

# Run all suites with: bash tests/default_scripts/default_scripts_test.sh
# Run one suite with: bash tests/default_scripts/default_scripts_test.sh startup
# Check script syntax with: bash tests/default_scripts/default_scripts_test.sh syntax
set -e

TESTS_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "$TESTS_DIR/helpers.sh"

WORKDIR=$(mktemp -d)
trap 'rmdir -- "$WORKDIR"' EXIT

CASE_COUNT=0
FAILED_CASES=0
SKIPPED_CASES=0
if [ "$#" -eq 0 ]; then
    test_files=()
    for test_file in "$TESTS_DIR"/*_test.sh; do
        if [ "$test_file" != "$TESTS_DIR/default_scripts_test.sh" ]; then
            test_files+=("$test_file")
        fi
    done
else
    test_files=()
    for suite in "$@"; do
        if [[ ! "$suite" =~ ^[a-z][a-z0-9_]*$ ]] || [ "$suite" = default_scripts ] \
                || [ ! -f "$TESTS_DIR/${suite}_test.sh" ]; then
            printf 'Unknown test suite: %s\n' "$suite" >&2
            exit 1
        fi
        test_files+=("$TESTS_DIR/${suite}_test.sh")
    done
fi
writeHeading "Testing default scripts of VPL for Moodle"
for test_file in "${test_files[@]}"; do
    source "$test_file"
done

if [ "$FAILED_CASES" -eq 0 ]; then
    printf 'OK '
else
    printf 'Fail '
fi
printf '%s/%s tests passed' "$((CASE_COUNT - FAILED_CASES - SKIPPED_CASES))" "$CASE_COUNT"
if [ "$SKIPPED_CASES" -ne 0 ]; then
    printf ' (%s skipped)' "$SKIPPED_CASES"
fi
printf '\n'
if [ "$FAILED_CASES" -ne 0 ]; then
    exit 1
fi
