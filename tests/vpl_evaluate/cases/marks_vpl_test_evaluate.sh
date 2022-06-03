#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e "Grade :=>> 3.50\$" "$VPLTESTOUTPUT" >/dev/null
