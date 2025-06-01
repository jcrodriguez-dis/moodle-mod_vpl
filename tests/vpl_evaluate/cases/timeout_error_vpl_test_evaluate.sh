#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e 'Global timeout' "$VPLTESTOUTPUT" >/dev/null
