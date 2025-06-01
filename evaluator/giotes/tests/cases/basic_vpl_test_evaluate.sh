#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "Grade :=>>10$"
assertOutput " 12 test(s) run/12 test(s) passed "
assertOutput ": Text$"
assertOutput ": Text multiline$"
assertOutput ": Text end$"
assertOutput ": Text multi-output$"
assertOutput ": Numbers$"
assertOutput ": Numbers multiline$"
assertOutput ": Numbers precision$"
assertOutput ": Numbers with asterisk$"
assertOutput ": Numbers multi-output$"
assertOutput ": Exact text$"
assertOutput ": Exact text with asterisk$"
assertOutput ": Regular expression$"
