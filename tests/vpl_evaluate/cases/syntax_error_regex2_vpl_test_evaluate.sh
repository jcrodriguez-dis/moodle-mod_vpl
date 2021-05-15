#!/bin/bash
if [ ! -s "$VPLTESTERRORS" ] ; then
    echo "No se"
    exit 1
fi
grep -e 'regular expression compilation error' "$VPLTESTOUTPUT" >/dev/null
