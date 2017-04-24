#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e "Grade :=>> 4\$" "$VPLTESTOUTPUT" >/dev/null
