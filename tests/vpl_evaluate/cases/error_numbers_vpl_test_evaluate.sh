#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e "Grade :=>> 5\.71$" "$VPLTESTOUTPUT" >/dev/null
