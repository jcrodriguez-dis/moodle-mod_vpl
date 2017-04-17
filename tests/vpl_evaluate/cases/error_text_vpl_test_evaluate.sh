#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    echo "The program has generated the following errors"
    cat $VPLTESTERRORS
    exit 1
fi
grep -e "Grade :=>> 6" "$VPLTESTOUTPUT" >/dev/null
if [ $? != 0 ] ; then
    echo "Test: $1 failed"
    cat "$VPLTESTOUTPUT"
    exit 1
fi
exit 0
