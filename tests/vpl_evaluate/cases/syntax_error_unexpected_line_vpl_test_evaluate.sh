#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e 'unexpected line' "$VPLTESTOUTPUT" >/dev/null
