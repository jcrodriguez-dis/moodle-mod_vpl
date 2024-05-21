#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "Grade :=>> 0$"
assertOutput "^Fail_message_1$"
assertOutput "^Fail_message_2$"
assertOutput "^Fail_message_4$"
grep -c -e "Program output" "$VPLTESTOUTPUT" | grep -e "^1$" > /dev/null
if [ "$?" != "0" ] ; then
    exit 1
fi
