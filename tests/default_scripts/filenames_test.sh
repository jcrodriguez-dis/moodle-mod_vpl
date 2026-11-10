#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

source "$TESTS_DIR/startup_helpers.sh"

while read -r filename_language; do
    for filename_stem in 'two words' 'cash$PRICE' 'literal[1]*' 'say"hi"' 'part&one'; do
        run_case "$filename_language: preserves filename <$filename_stem>" \
            test_language_startup "$filename_language" '' "$filename_stem"
    done
done < <(get_startup_languages)

while read -r filename_language filename_program; do
    run_case "$filename_language: fallback $filename_program preserves special filenames" \
        test_language_startup "$filename_language" "$filename_program" 'two $PRICE "words" & more'
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
