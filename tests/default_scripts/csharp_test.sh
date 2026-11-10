#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function test_csharp_gui_spaces {
    local project_name=${2:-App name}
    export EXPECTED_PROJECT="$project_name.csproj"
    export EXPECTED_EXECUTABLE="bin/Debug/net8.0/$project_name"
    CASE_FILES+=("$EXPECTED_PROJECT" "$EXPECTED_EXECUTABLE")
    CASE_DIRECTORIES=(bin/Debug/net8.0 bin/Debug bin)
    touch "$EXPECTED_PROJECT"
    prepare_submission "$EXPECTED_PROJECT"
    function dotnet {
        assert_equal 3 "$#"
        assert_equal build "$1"
        assert_equal -v=q "$2"
        assert_equal "$EXPECTED_PROJECT" "$3"
        mkdir -p bin/Debug/net8.0
        printf '#!/bin/bash\n# System.Windows.Forms\nprintf "GUI program ran\\n"\n' > "$EXPECTED_EXECUTABLE"
        chmod +x "$EXPECTED_EXECUTABLE"
    }
    export -f dotnet
    bash "$SCRIPTS_DIR/$1" batch
    assert_file vpl_wexecution
    bash -n vpl_wexecution
    assert_equal 'GUI program ran' "$(bash vpl_wexecution)"
}

run_case 'C# detects GUI executables with spaces' test_csharp_gui_spaces csharp_run.sh
run_case 'C# NUnit detects GUI executables with spaces' test_csharp_gui_spaces csharp-nunit_run.sh
run_case 'C# preserves special characters in project and executable paths' \
    test_csharp_gui_spaces csharp_run.sh 'App $PRICE "quoted" [1]* & more'
run_case 'C# NUnit preserves special characters in project and executable paths' \
    test_csharp_gui_spaces csharp-nunit_run.sh 'App $PRICE "quoted" [1]* & more'
