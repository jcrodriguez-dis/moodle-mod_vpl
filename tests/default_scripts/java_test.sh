#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_java_file_arguments {
    CASE_FILES+=("folder with spaces/First file.java" 'literal[1]*.java' literal1match.java
        ordinary.java ordinary.class "library with spaces.jar")
    CASE_DIRECTORIES+=("folder with spaces")
    mkdir "folder with spaces"
    touch "folder with spaces/First file.java" 'literal[1]*.java' literal1match.java ordinary.java
    touch "library with spaces.jar"
    prepare_submission "folder with spaces/First file.java" 'literal[1]*.java'

    function check_arguments {
        assert_equal 4 "$#"
        assert_equal "$VPL_SUBFILE0" "$1"
        assert_equal "$VPL_SUBFILE1" "$2"
        local filename
        for filename in "$@"; do
            assert_file "$filename"
        done
    }
    function javac {
        assert_equal 7 "$#"
        assert_equal -Xlint:deprecation "$1"
        assert_equal -g "$2"
        assert_equal -parameters "$3"
        shift 3
        check_arguments "$@"
        touch ordinary.class
    }
    function java {
        if [ "$1" == "-version" ]; then
            printf 'openjdk version "17.0.1"\n' >&2
        else
            assert_equal -enableassertions "$1"
            assert_equal ordinary "$2"
            case "$CLASSPATH" in
                *":library with spaces.jar") ;;
                *) printf 'FAIL: generated execution lost the JAR classpath\n' >&2; exit 1 ;;
            esac
        fi
    }
    function javap {
        assert_equal ./ordinary.class "$1"
        printf '  public static void main(java.lang.String[]);\n'
    }
    function jgrasp {
        assert_equal ordinary.java "$1"
    }
    export -f assert_file check_arguments javac java javap jgrasp
    bash "$SCRIPTS_DIR/java_run.sh" batch '-g -parameters'
    bash -n vpl_execution
    bash vpl_execution
    bash "$SCRIPTS_DIR/java_debug.sh" batch '-g -parameters'
    bash -n vpl_wexecution
    grep -q '^jgrasp ordinary.java$' vpl_wexecution
}

function test_java_dollar_filename {
    export EXPECTED_FILENAME='Price$Main.java'
    export EXPECTED_CLASS='Price$Main'
    CASE_FILES+=("$EXPECTED_FILENAME" "$EXPECTED_CLASS.class")
    touch "$EXPECTED_FILENAME"
    prepare_submission "$EXPECTED_FILENAME"
    function javac {
        assert_equal 2 "$#"
        assert_equal -Xlint:deprecation "$1"
        assert_equal "$EXPECTED_FILENAME" "$2"
        touch "$EXPECTED_CLASS.class"
    }
    function javap {
        assert_equal "./$EXPECTED_CLASS.class" "$1"
        printf '  public static void main(java.lang.String[]);\n'
    }
    function java {
        if [ "$1" == "-version" ]; then
            printf 'openjdk version "17.0.1"\n' >&2
        else
            assert_equal 2 "$#"
            assert_equal -enableassertions "$1"
            assert_equal "$EXPECTED_CLASS" "$2"
        fi
    }
    export -f javac javap java
    function jgrasp {
        check_filename "$@"
    }
    function wait_end {
        assert_equal 1 "$#"
        assert_equal jgrasp "$1"
    }
    export -f jgrasp
    unset Main
    bash "$SCRIPTS_DIR/java_run.sh" batch
    assert_file vpl_execution
    bash vpl_execution
    bash "$SCRIPTS_DIR/java_debug.sh" batch
    assert_file vpl_wexecution
    bash -n vpl_wexecution
    # The launcher sources only the environment, so this intercepts process polling.
    export -f wait_end
    bash vpl_wexecution
}

run_case 'Java run/debug preserve file arguments and JAR classpaths' test_java_file_arguments
run_case 'Java preserves dollar signs in class names' test_java_dollar_filename
