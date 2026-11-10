#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

source "$TESTS_DIR/startup_helpers.sh"

run_case 'Mock harness rejects incorrect arguments and reports swallowed failures' test_mock_rejects_arguments
run_case 'Mock harness rejects incorrect compiler response files' test_mock_rejects_response

while read -r startup_language; do
    run_case "$startup_language: mocked compile/preparation and execution" \
        test_language_startup "$startup_language"
done < <(get_startup_languages)

while read -r startup_language startup_program; do
    run_case "$startup_language: fallback to $startup_program" \
        test_language_startup "$startup_language" "$startup_program"
done <<'FALLBACKS'
csharp csc
csharp mcs
csharp-nunit csc
csharp-nunit mcs
haskell hugs
javascript nodejs
matlab octave
minizinc mzn-fzn
pascal gpc
php php5
python python
python python2
scheme mzscheme
typescript nodejs
vhdl gvhdl
FALLBACKS
