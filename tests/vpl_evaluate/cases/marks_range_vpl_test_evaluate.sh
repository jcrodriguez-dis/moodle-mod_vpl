#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e "Grade :=>>73.50\$" "$VPLTESTOUTPUT" >/dev/null
