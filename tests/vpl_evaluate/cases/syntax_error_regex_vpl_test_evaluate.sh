#!/bin/bash
if [ ! -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e 'invalid flag in regex output' "$VPLTESTOUTPUT" >/dev/null
