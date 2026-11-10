#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_fsharp_xml_filename {
    CASE_FILES+=('part&one.fs' default.fsproj)
    touch 'part&one.fs'
    prepare_submission 'part&one.fs'
    function dotnet {
        if [ "$1" == "--version" ]; then
            printf '8.0.0\n'
        else
            # Only inspect project generation; do not simulate a successful build.
            return 1
        fi
    }
    export -f dotnet
    bash "$SCRIPTS_DIR/fsharp_run.sh" batch
    assert_file default.fsproj
    if ! grep -Fq 'Include="part&amp;one.fs"' default.fsproj; then
        printf 'FAIL: generated project did not XML-escape the filename\n' >&2
        exit 1
    fi
}

run_case 'F# XML-escapes source filenames' test_fsharp_xml_filename
