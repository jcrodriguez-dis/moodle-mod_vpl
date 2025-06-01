#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
# Test global input
assertOutputFalse "Error Test global input"
# Test local input
assertOutput "Correct Test local input"
# Test global fail message
assertOutput "Custom fail message"
# Test exit code fail message
assertOutput "Custom exit code message"

# Test exit code global fail message
assertOutput "Custom exit code message"
# Test exit code local fail message
assertOutput "Local exit code message"

# Test local exit code and program run
assertOutputFalse "Error local exit code"

# Test local exit code and program run and global parameter
assertOutputFalse "Error program global parameter"

# Test local exit code and program run and local parameter
assertOutputFalse "Error program local parameters"

# Test local exit code and program run and local parameter
assertOutput "Correct program local parameters"

# Test local exit code and program run and local parameter
assertOutput "Pass test message used"

assertOutputFalse "Grade :=>>10\$"
