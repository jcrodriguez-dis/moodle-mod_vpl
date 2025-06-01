#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutputFalse "Case pass only exit code"
assertOutputFalse "Case pass only output"
assertOutput "Case fail exit code and output"
assertOutputFalse "Case pass exit code exclusive and output"
assertOutput "Case fail exit code exclusive"
assertOutput "Case fail output exit code exclusive"
