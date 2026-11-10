#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_common_file_arrays {
    CASE_FILES+=("source files.txt" "decoded file.txt.b64" "decoded file.txt"
        "folder with spaces/First file.java" 'literal[1]*.java' literal1match.java ordinary.java)
    CASE_DIRECTORIES+=("folder with spaces")
    mkdir "folder with spaces"
    touch "folder with spaces/First file.java" 'literal[1]*.java' literal1match.java ordinary.java
    printf 'ZGVjb2RlZA==' > "decoded file.txt.b64"
    prepare_submission "folder with spaces/First file.java" 'literal[1]*.java'

    local original_ifs=$IFS
    source common_script.sh
    assert_equal "$original_ifs" "$IFS"
    assert_equal decoded "$(cat "decoded file.txt")"
    assert_equal 0 "${#SOURCE_FILES_ARRAY[@]}"
    assert_equal 2 "${#VPL_SUBFILES_ARRAY[@]}"
    assert_equal "$VPL_SUBFILE0" "${VPL_SUBFILES_ARRAY[0]}"
    assert_equal "$VPL_SUBFILE1" "${VPL_SUBFILES_ARRAY[1]}"

    get_source_files java
    assert_equal 4 "${#SOURCE_FILES_ARRAY[@]}"
    assert_equal "$VPL_SUBFILE0" "${SOURCE_FILES_ARRAY[0]}"
    assert_equal "$VPL_SUBFILE1" "${SOURCE_FILES_ARRAY[1]}"
    assert_equal "$VPL_SUBFILE0" "$SOURCE_FILE0"
    local filename
    for filename in "${SOURCE_FILES_ARRAY[@]}"; do
        assert_file "$filename"
    done

    generate_file_of_files "source files.txt"
    local -a file_lines
    mapfile -t file_lines < "source files.txt"
    assert_equal 4 "${#file_lines[@]}"
    local i
    for i in "${!SOURCE_FILES_ARRAY[@]}"; do
        assert_equal "\"${SOURCE_FILES_ARRAY[i]}\"" "${file_lines[i]}"
    done
    generate_file_of_files "source files.txt" NOQUOTE
    mapfile -t file_lines < "source files.txt"
    for i in "${!SOURCE_FILES_ARRAY[@]}"; do
        assert_equal "${SOURCE_FILES_ARRAY[i]}" "${file_lines[i]}"
    done

    if get_source_files missing NOERROR; then
        printf 'FAIL: missing extension unexpectedly succeeded\n' >&2
        exit 1
    fi
    assert_equal 0 "${#SOURCE_FILES_ARRAY[@]}"
    generate_file_of_files "source files.txt"
    assert_equal 0 "$(wc -c < "source files.txt" | tr -d '[:space:]')"
}

run_case 'Common file arrays, ordering, response files, base64 and empty lookups' test_common_file_arrays

function test_response_file_escaping {
    prepare_submission
    source common_script.sh
    SOURCE_FILES_ARRAY=('say"hi".c' 'path\name.c' 'two words.c')
    generate_file_of_files response.txt
    local -a lines
    mapfile -t lines < response.txt
    assert_equal 3 "${#lines[@]}"
    assert_equal '"say\"hi\".c"' "${lines[0]}"
    assert_equal '"path\\name.c"' "${lines[1]}"
    assert_equal '"two words.c"' "${lines[2]}"
    generate_file_of_files response.txt NOQUOTE
    mapfile -t lines < response.txt
    local index
    for index in "${!SOURCE_FILES_ARRAY[@]}"; do
        assert_equal "${SOURCE_FILES_ARRAY[index]}" "${lines[index]}"
    done
}

run_case 'Response files escape quotes and backslashes without changing NOQUOTE' test_response_file_escaping
