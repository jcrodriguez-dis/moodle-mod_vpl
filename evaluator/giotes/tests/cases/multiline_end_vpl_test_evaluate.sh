#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "Test 1: Test end multiline input"
assertOutput "^>input = algo$"
assertOutput "^>output = algo$"
assertOutput "^>fail output message = algo"

assertOutput "Test 2: Test end multiline output"
assertOutput "^>algo$"
assertOutput "^>input =$"
assertOutput "^>expected exit code = 0$"
assertOutput "^>pass output message = algo$"

assertOutput "Test 3: Test end multiline pass mesage"
assertOutput "^>algo$"
assertOutput "^>input =$"
assertOutput "^>expected exit code = 0$"
assertOutput "^>pass output message = algo$"
